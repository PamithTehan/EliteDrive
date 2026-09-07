<div id="tab-drivers" class="mgmt-tab-content" style="display: <?= $activeTab === 'drivers' ? 'block' : 'none' ?>;">
    <table class="table" style="width: 100%;">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Daily Fee</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($drivers as $d): ?>
                <tr>
                    <td>
                        <div><?= escapeHtml($d['full_name']) ?></div>
                        <div style="font-size:12px; color:var(--color-secondary);"><?= escapeHtml($d['transmission_preference']) ?></div>
                    </td>
                    <td><?= escapeHtml($d['email']) ?></td>
                    <td>LKR <?= number_format($d['daily_fee'], 2) ?></td>
                    <td>
                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to remove this driver?');" style="display:inline;">
                            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="tab" value="drivers">
                            <input type="hidden" name="id" value="<?= $d['id'] ?>">
                            <button type="submit" class="btn btn-outline btn-sm" style="color: var(--color-danger); border-color: var(--color-danger);">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($drivers)): ?>
                <tr><td colspan="4" style="text-align:center;">No drivers found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if ($totalPagesDrivers > 1): ?>
    <div style="display: flex; justify-content: center; gap: 8px; margin-top: 20px;">
        <?php for ($i = 1; $i <= $totalPagesDrivers; $i++): ?>
            <a href="?tab=drivers&page_drivers=<?= $i ?>" class="btn <?= $i === $pageDrivers ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
