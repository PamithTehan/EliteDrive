<div id="tab-vehicles" class="mgmt-tab-content" style="display: <?= $activeTab === 'vehicles' ? 'block' : 'none' ?>;">
    <table class="table" style="width: 100%;">
        <thead>
            <tr>
                <th>Vehicle</th>
                <th>Owner</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vehicles as $v): ?>
                <tr>
                    <td>
                        <div><?= escapeHtml($v['make'] . ' ' . $v['model'] . ' (' . $v['yom'] . ')') ?></div>
                        <div style="font-size:12px; color:var(--color-secondary);"><?= escapeHtml($v['category']) ?></div>
                    </td>
                    <td><?= escapeHtml($v['owner_name']) ?></td>
                    <td>
                        <span class="badge" style="background: var(--color-surface-variant); color: var(--color-primary); border-radius: 4px; padding: 4px 8px; font-size: 11px;">
                            <?= escapeHtml(ucfirst(str_replace('_', ' ', $v['status']))) ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to remove this vehicle?');" style="display:inline;">
                            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="delete_vehicle">
                            <input type="hidden" name="id" value="<?= $v['id'] ?>">
                            <button type="submit" class="btn btn-outline btn-sm" style="color: var(--color-danger); border-color: var(--color-danger);">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($vehicles)): ?>
                <tr><td colspan="4" style="text-align:center;">No vehicles found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if ($totalPagesVehicles > 1): ?>
    <div style="display: flex; justify-content: center; gap: 8px; margin-top: 20px;">
        <?php for ($i = 1; $i <= $totalPagesVehicles; $i++): ?>
            <a href="?tab=vehicles&page_vehicles=<?= $i ?>" class="btn <?= $i === $pageVehicles ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
