<?php if ($pagination['pages'] > 1): ?>
    <nav aria-label="<?= e($paginationLabel) ?>">
        <ul class="pagination flex-wrap">
            <?php for ($pageNumber = 1; $pageNumber <= $pagination['pages']; $pageNumber++): ?>
                <li class="page-item <?= $pageNumber === $pagination['page'] ? 'active' : '' ?>">
                    <a class="page-link" href="<?= e($paginationUrl . '?' . http_build_query(['search' => $search, 'page' => $pageNumber])) ?>"
                       <?= $pageNumber === $pagination['page'] ? 'aria-current="page"' : '' ?> data-no-translate><?= $pageNumber ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>
