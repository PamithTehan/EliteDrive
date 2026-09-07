<div id="tab-bookings" class="mgmt-tab-content" style="display: <?= $activeTab === 'bookings' ? 'block' : 'none' ?>;">
    <table class="table" style="width: 100%;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Vehicle & Owner</th>
                <th>Borrower</th>
                <th>Dates</th>
                <th>Total Price</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td>#<?= $b['id'] ?></td>
                    <td>
                        <div style="font-weight: 500;"><?= escapeHtml($b['make'] . ' ' . $b['model']) ?></div>
                        <div style="font-size:12px; color:var(--color-secondary);">Owner: <?= escapeHtml($b['owner_name']) ?></div>
                    </td>
                    <td><?= escapeHtml($b['borrower_name']) ?></td>
                    <td>
                        <div style="font-size: 13px;"><?= date('M j, Y H:i', strtotime($b['pickup_date'])) ?></div>
                        <div style="font-size: 13px; color: var(--color-secondary);">to <?= date('M j, Y H:i', strtotime($b['return_date'])) ?></div>
                    </td>
                    <td style="font-weight: 500;">LKR <?= number_format($b['total_price'], 2) ?></td>
                    <td>
                        <span class="badge" style="background: var(--color-surface-variant); color: var(--color-primary); border-radius: 4px; padding: 4px 8px; font-size: 11px;">
                            <?= escapeHtml(ucfirst(str_replace('_', ' ', $b['status']))) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($bookings)): ?>
                <tr><td colspan="6" style="text-align:center;">No bookings found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if ($totalPagesBookings > 1): ?>
    <div style="display: flex; justify-content: center; gap: 8px; margin-top: 20px;">
        <?php for ($i = 1; $i <= $totalPagesBookings; $i++): ?>
            <a href="?tab=bookings&page_bookings=<?= $i ?><?= $bookingStatus ? '&booking_status='.urlencode($bookingStatus) : '' ?>" class="btn <?= $i === $pageBookings ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
