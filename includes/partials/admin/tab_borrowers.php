<div id="tab-borrowers" class="mgmt-tab-content" style="display: <?= $activeTab === 'borrowers' ? 'block' : 'none' ?>;">
    <table class="table" style="width: 100%;">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($borrowers as $b): ?>
                <tr>
                    <td><?= escapeHtml($b['full_name']) ?></td>
                    <td><?= escapeHtml($b['email']) ?></td>
                    <td><?= date('M j, Y', strtotime($b['created_at'])) ?></td>
                    <td>
                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to remove this borrower?');" style="display:inline;">
                            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="tab" value="borrowers">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <button type="submit" title="Remove" style="background: none; border: none; color: var(--color-danger); cursor: pointer; padding: 4px; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px; transition: background 0.2s;" onmouseover="this.style.backgroundColor='#fee2e2';" onmouseout="this.style.backgroundColor='transparent';">
                                <span class="material-symbols-outlined" style="font-size: 20px;">delete</span>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($borrowers)): ?>
                <tr><td colspan="4" style="text-align:center;">No borrowers found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if ($totalPagesBorrowers > 1): ?>
    <div style="display: flex; justify-content: center; gap: 8px; margin-top: 20px;">
        <?php for ($i = 1; $i <= $totalPagesBorrowers; $i++): ?>
            <a href="?tab=borrowers&page_borrowers=<?= $i ?>" class="btn <?= $i === $pageBorrowers ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
