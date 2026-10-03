<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AuditLogService.php';

class PropertyService
{
    public static function allowedChildTypes(?string $parentType): array
    {
        return match ($parentType) {
            null => ['building'],
            'building' => ['wing', 'floor'],
            'wing' => ['floor'],
            'floor' => ['room'],
            default => [],
        };
    }

    public static function findForClient(int $clientId, int $nodeId): ?array
    {
        $stmt = db()->prepare('SELECT id, parent_id, node_type, name, sort_order FROM property_nodes WHERE client_id = :client_id AND id = :id');
        $stmt->execute(['client_id' => $clientId, 'id' => $nodeId]);
        $node = $stmt->fetch();

        return $node !== false ? $node : null;
    }

    public static function listTreeForClient(int $clientId): array
    {
        $stmt = db()->prepare('SELECT id, parent_id, node_type, name, sort_order FROM property_nodes WHERE client_id = :client_id ORDER BY parent_id, node_type, sort_order, id');
        $stmt->execute(['client_id' => $clientId]);

        return self::buildTree($stmt->fetchAll());
    }

    public static function buildTree(array $nodes): array
    {
        $children = [];
        foreach ($nodes as $node) {
            $children[$node['parent_id'] ?? 0][$node['node_type']][] = $node;
        }

        $tree = [];
        $appendChildren = function (int $parentId, int $depth, array $parentPath) use (&$appendChildren, &$tree, $children): void {
            $typeOrder = ['building' => 0, 'wing' => 1, 'floor' => 2, 'room' => 3];
            $groups = $children[$parentId] ?? [];
            uksort($groups, static fn (string $first, string $second): int => ($typeOrder[$first] ?? 99) <=> ($typeOrder[$second] ?? 99));

            foreach ($groups as $siblings) {
                usort($siblings, static fn (array $first, array $second): int => ((int) $first['sort_order'] <=> (int) $second['sort_order']) ?: ((int) $first['id'] <=> (int) $second['id']));
                $siblingCount = count($siblings);
                foreach ($siblings as $position => $node) {
                    $node['depth'] = $depth;
                    $node['path'] = array_merge($parentPath, [$node['name']]);
                    $node['sibling_position'] = $position;
                    $node['sibling_count'] = $siblingCount;
                    $tree[] = $node;
                    $appendChildren((int) $node['id'], $depth + 1, $node['path']);
                }
            }
        };
        $appendChildren(0, 0, []);

        return $tree;
    }

    /** @return array{0: bool, 1: string} */
    public static function create(int $clientId, array $data, int $actorUserId): array
    {
        $name = trim(is_string($data['name'] ?? null) ? $data['name'] : '');
        $type = trim(is_string($data['node_type'] ?? null) ? $data['node_type'] : '');
        $parentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;

        if ($name === '' || preg_match('//u', $name) !== 1 || preg_match_all('/./us', $name) > 191) {
            return [false, 'Enter a name of at most 191 characters.'];
        }

        $parent = $parentId !== null ? self::findForClient($clientId, $parentId) : null;
        if ($parentId !== null && $parent === null) {
            return [false, 'Parent not found for this client.'];
        }
        if (!in_array($type, self::allowedChildTypes($parent['node_type'] ?? null), true)) {
            return [false, 'Choose a valid location type for the selected parent.'];
        }

        if ($parentId === null) {
            $orderQuery = db()->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM property_nodes WHERE client_id = :client_id AND parent_id IS NULL AND node_type = :node_type');
            $orderQuery->execute(['client_id' => $clientId, 'node_type' => $type]);
        } else {
            $orderQuery = db()->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM property_nodes WHERE client_id = :client_id AND parent_id = :parent_id AND node_type = :node_type');
            $orderQuery->execute(['client_id' => $clientId, 'parent_id' => $parentId, 'node_type' => $type]);
        }
        $sortOrder = (int) $orderQuery->fetchColumn() + 1;

        try {
            $stmt = db()->prepare(
                'INSERT INTO property_nodes (client_id, parent_id, node_type, name, sort_order)
                 VALUES (:client_id, :parent_id, :node_type, :name, :sort_order)'
            );
            $stmt->execute(['client_id' => $clientId, 'parent_id' => $parentId, 'node_type' => $type, 'name' => $name, 'sort_order' => $sortOrder]);
        } catch (PDOException $exception) {
            return [false, 'Could not save the property location.'];
        }

        AuditLogService::log($actorUserId, $clientId, 'property_node.created', 'property_nodes', (string) db()->lastInsertId());

        return [true, 'Location added.'];
    }

    /** @return array{0: bool, 1: string} */
    public static function moveSibling(int $clientId, int $nodeId, string $direction, int $actorUserId): array
    {
        if (!in_array($direction, ['up', 'down'], true)) {
            return [false, 'Invalid move direction.'];
        }
        $node = self::findForClient($clientId, $nodeId);
        if ($node === null) {
            return [false, 'Location not found for this client.'];
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            if ($node['parent_id'] === null) {
                $stmt = $pdo->prepare('SELECT id, sort_order FROM property_nodes WHERE client_id = :client_id AND parent_id IS NULL AND node_type = :node_type ORDER BY sort_order, id FOR UPDATE');
                $stmt->execute(['client_id' => $clientId, 'node_type' => $node['node_type']]);
            } else {
                $stmt = $pdo->prepare('SELECT id, sort_order FROM property_nodes WHERE client_id = :client_id AND parent_id = :parent_id AND node_type = :node_type ORDER BY sort_order, id FOR UPDATE');
                $stmt->execute(['client_id' => $clientId, 'parent_id' => (int) $node['parent_id'], 'node_type' => $node['node_type']]);
            }
            $siblings = $stmt->fetchAll();
            $position = array_search($nodeId, array_map(static fn (array $sibling): int => (int) $sibling['id'], $siblings), true);
            if ($position === false) {
                $pdo->rollBack();
                return [false, 'Location not found among its siblings.'];
            }
            $targetPosition = $position + ($direction === 'up' ? -1 : 1);
            if (!isset($siblings[$targetPosition])) {
                $pdo->rollBack();
                return [false, 'Location is already at this end of the list.'];
            }
            $moving = $siblings[$position];
            $siblings[$position] = $siblings[$targetPosition];
            $siblings[$targetPosition] = $moving;

            $update = $pdo->prepare('UPDATE property_nodes SET sort_order = :sort_order WHERE client_id = :client_id AND id = :id');
            foreach ($siblings as $index => $sibling) {
                $update->execute(['sort_order' => $index + 1, 'client_id' => $clientId, 'id' => (int) $sibling['id']]);
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return [false, 'Could not change the location order.'];
        }

        AuditLogService::log($actorUserId, $clientId, 'property_node.reordered', 'property_nodes', (string) $nodeId, null, $direction);

        return [true, 'Location order updated.'];
    }

    /** @return array{0: bool, 1: string} */
    public static function rename(int $clientId, int $nodeId, string $name, int $actorUserId): array
    {
        $name = trim($name);
        if ($name === '' || preg_match('//u', $name) !== 1 || preg_match_all('/./us', $name) > 191) {
            return [false, 'Enter a name of at most 191 characters.'];
        }
        if (self::findForClient($clientId, $nodeId) === null) {
            return [false, 'Location not found for this client.'];
        }

        try {
            $stmt = db()->prepare('UPDATE property_nodes SET name = :name WHERE id = :id AND client_id = :client_id');
            $stmt->execute(['name' => $name, 'id' => $nodeId, 'client_id' => $clientId]);
        } catch (PDOException $exception) {
            return [false, 'Could not rename the property location.'];
        }

        AuditLogService::log($actorUserId, $clientId, 'property_node.renamed', 'property_nodes', (string) $nodeId);

        return [true, 'Location renamed.'];
    }
}