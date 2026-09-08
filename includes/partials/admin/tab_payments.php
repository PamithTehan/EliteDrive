<div id="tab-payments" class="mgmt-tab-content" style="display: <?= $activeTab === 'payments' ? 'block' : 'none' ?>;">
    
    <div class="table-responsive">
        <?php if (empty($payments)): ?>
            <p style="text-align:center; padding: 40px; color: var(--color-secondary);">No payments collected yet.</p>
        <?php else: ?>
            <table class="table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>Booking ID</th>
                        <th>Date</th>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Stripe Intent ID</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td>#<?= escapeHtml($p['id']) ?></td>
                            <td><a href="<?= baseUrl('/admin/management.php?tab=bookings') ?>">#<?= escapeHtml($p['booking_id']) ?></a></td>
                            <td><?= date('M j, Y H:i', strtotime($p['created_at'])) ?></td>
                            <td><?= escapeHtml($p['user_name']) ?></td>
                            <td style="font-weight: 600; color: var(--color-primary);"><?= escapeHtml($p['currency']) ?> <?= number_format($p['amount'], 2) ?></td>
                            <td><span style="font-family: monospace; font-size: 12px; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;"><?= escapeHtml($p['stripe_payment_intent_id']) ?></span></td>
                            <td>
                                <?php if ($p['status'] === 'succeeded'): ?>
                                    <span class="badge" style="background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;">Succeeded</span>
                                <?php elseif ($p['status'] === 'pending'): ?>
                                    <span class="badge" style="background: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;">Pending</span>
                                <?php elseif ($p['status'] === 'failed' || $p['status'] === 'refunded'): ?>
                                    <span class="badge" style="background: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;"><?= escapeHtml($p['status']) ?></span>
                                <?php else: ?>
                                    <span class="badge" style="background: #e2e8f0; color: #475569; padding: 4px 8px; border-radius: 4px; font-size: 11px; text-transform: uppercase;"><?= escapeHtml($p['status']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($totalPagesPayments > 1): ?>
                <div class="pagination" style="margin-top: 24px; display: flex; justify-content: center; gap: 8px;">
                    <?php for ($i = 1; $i <= $totalPagesPayments; $i++): ?>
                        <a href="?tab=payments&page_payments=<?= $i ?>" class="page-link" style="padding: 8px 12px; border: 1px solid var(--color-outline); border-radius: 4px; text-decoration: none; color: <?= $i === $pagePayments ? 'white' : 'var(--color-primary)' ?>; background-color: <?= $i === $pagePayments ? 'var(--color-primary)' : 'white' ?>;">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
