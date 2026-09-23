<?php
/**
 * WoodCon - Trang Quyền lợi thành viên (VIP / Diamond)
 */

declare(strict_types=1);

namespace WoodCon\Controllers;

use WoodCon\User;

class BenefitsController extends BaseController
{
    public function index(): void
    {
        $user = current_user();
        $tiers = User::query(
            'SELECT * FROM membership_tiers WHERE status = 1 ORDER BY min_total_spent ASC'
        );

        $__b = User::query(
            'SELECT mb.tier_id, mb.label
               FROM membership_benefits mb
              WHERE mb.status = 1
              GROUP BY mb.tier_id, mb.label
              ORDER BY MIN(mb.sort_order) ASC, mb.label ASC'
        );
        $benefits = [];
        foreach ($__b as $__r) {
            $benefits[(int)$__r['tier_id']][] = $__r['label'];
        }

        $__r = User::query(
            'SELECT label
               FROM membership_benefits
              WHERE status = 1
              GROUP BY label
              ORDER BY MIN(sort_order) ASC, id ASC'
        );
        $benefitRows = array_column($__r, 'label');

        $current = null;
        $next = null;
        if ($user) {
            $current = User::membershipTier((int)$user['id']);
            $next = User::nextMembershipTier((int)$user['id']);
        }

        $this->render('benefits', [
            'pageTitle' => 'Quyền lợi thành viên - WoodCon',
            'metaDescription' => 'Quyền lợi hạng thành viên WoodCon: giảm giá theo hạng, tích điểm thưởng và đặt COD không cần OTP cho VIP & Diamond.',
            'tiers' => $tiers,
            'benefits' => $benefits,
            'benefitRows' => $benefitRows,
            'current' => $current,
            'next' => $next,
            'user' => $user,
        ]);
    }
}