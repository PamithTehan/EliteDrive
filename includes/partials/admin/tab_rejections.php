<div id="tab-rejections" class="mgmt-tab-content" style="display: <?= $activeTab === 'rejections' ? 'block' : 'none' ?>;">
    <table class="table" style="width: 100%;">
        <thead>
            <tr>
                <th>Type</th>
                <th>Rejection Reason</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rejections as $r): ?>
                <tr>
                    <td>
                        <span class="badge" style="background: var(--color-surface-variant); color: var(--color-primary); border-radius: 4px; padding: 4px 8px; font-size: 11px;">
                            <?= escapeHtml($r['type']) ?>
                        </span>
                    </td>
                    <td><?= nl2br(escapeHtml($r['reason'] ?: 'No reason provided')) ?></td>
                    <td style="color:var(--color-secondary); font-size:14px;"><?= date('M d, Y H:i', strtotime($r['date'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rejections)): ?>
                <tr><td colspan="3" style="text-align:center;">No rejection logs found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if ($totalPagesRejections > 1): ?>
    <div style="display: flex; justify-content: center; gap: 8px; margin-top: 20px;">
        <?php for ($i = 1; $i <= $totalPagesRejections; $i++): ?>
            <a href="?tab=rejections&page_rejections=<?= $i ?>" class="btn <?= $i === $pageRejections ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
