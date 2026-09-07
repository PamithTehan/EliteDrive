<div id="tab-commissions" class="mgmt-tab-content" style="display: <?= $activeTab === 'commissions' ? 'block' : 'none' ?>;">
    
    <div style="display: flex; gap: 20px; margin-bottom: 24px;">
        <div style="background: white; border: 1px solid var(--color-outline); border-radius: 8px; padding: 20px; flex: 1;">
            <div style="color: var(--color-secondary); font-size: 14px; margin-bottom: 8px;">Total Platform Revenue</div>
            <div style="font-size: 28px; font-weight: 700; color: var(--color-primary);">LKR <?= number_format($totalPlatformRevenue, 2) ?></div>
        </div>
    </div>

    <div class="table-responsive">
        <?php if (empty($commissions)): ?>
            <p style="text-align:center; padding: 40px; color: var(--color-secondary);">No commissions collected yet.</p>
        <?php else: ?>
            <table class="mgmt-table">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Date</th>
                        <th>Vehicle</th>
                        <th>Borrower</th>
                        <th>Base Price</th>
                        <th>Commission Rate</th>
                        <th>Commission Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commissions as $c): ?>
                        <tr>
                            <td>#<?= escapeHtml($c['id']) ?></td>
                            <td><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
                            <td><?= escapeHtml($c['make'] . ' ' . $c['model']) ?></td>
                            <td><?= escapeHtml($c['borrower_name']) ?></td>
                            <td>LKR <?= number_format($c['total_price'] - $c['commission_amount'], 2) ?></td>
                            <td><?= (float)$c['commission_rate'] ?>%</td>
                            <td style="font-weight: 600; color: var(--color-primary);">LKR <?= number_format($c['commission_amount'], 2) ?></td>
                            <td>
                                <?php if ($c['status'] === 'confirmed' || $c['status'] === 'active'): ?>
                                    <span class="badge" style="background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;">Active</span>
                                <?php elseif ($c['status'] === 'completed' || $c['status'] === 'reviewed'): ?>
                                    <span class="badge" style="background: #e0e7ff; color: #3730a3; padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;">Completed</span>
                                <?php elseif ($c['status'] === 'cancelled' || $c['status'] === 'rejected' || $c['status'] === 'disputed'): ?>
                                    <span class="badge" style="background: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;"><?= escapeHtml($c['status']) ?></span>
                                <?php else: ?>
                                    <span class="badge" style="background: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;"><?= escapeHtml($c['status']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($totalPagesCommissions > 1): ?>
                <div class="pagination" style="margin-top: 24px; display: flex; justify-content: center; gap: 8px;">
                    <?php for ($i = 1; $i <= $totalPagesCommissions; $i++): ?>
                        <a href="?tab=commissions&page=<?= $i ?>" class="page-link" style="padding: 8px 12px; border: 1px solid var(--color-outline); border-radius: 4px; text-decoration: none; color: <?= $i === $page ? 'white' : 'var(--color-primary)' ?>; background-color: <?= $i === $page ? 'var(--color-primary)' : 'white' ?>;">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
