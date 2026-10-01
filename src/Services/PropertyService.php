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
        $stmt = db()->prepare('SELECT id, parent_id, node_type, name FROM property_nodes WHERE client_id = :client_id AND id = :id');
        $stmt->execute(['client_id' => $clientId, 'id' => $nodeId]);
        $node = $stmt->fetch();

        return $node !== false ? $node : null;
    }

    public static function listTreeForClient(int $clientId): array
    {
        $stmt = db()->prepare('SELECT id, parent_id, node_type, name FROM property_nodes WHERE client_id = :client_id ORDER BY id');
        $stmt->execute(['client_id' => $clientId]);

        $children = [];
        foreach ($stmt->fetchAll() as $node) {
            $children[$node['parent_id'] ?? 0][] = $node;
        }

        $tree = [];
        $appendChildren = function (int $parentId, int $depth) use (&$appendChildren, &$tree, $children): void {
            foreach ($children[$parentId] ?? [] as $node) {
                $node['depth'] = $depth;
                $tree[] = $node;
                $appendChildren((int) $node['id'], $depth + 1);
            }
        };
        $appendChildren(0, 0);

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

        try {
            $stmt = db()->prepare(
                'INSERT INTO property_nodes (client_id, parent_id, node_type, name)
                 VALUES (:client_id, :parent_id, :node_type, :name)'
            );
            $stmt->execute(['client_id' => $clientId, 'parent_id' => $parentId, 'node_type' => $type, 'name' => $name]);
        } catch (PDOException $exception) {
            return [false, 'Could not save the property location.'];
        }

        AuditLogService::log($actorUserId, $clientId, 'property_node.created', 'property_nodes', (string) db()->lastInsertId());

        return [true, 'Location added.'];
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