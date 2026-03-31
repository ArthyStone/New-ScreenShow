<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Models\UserModel;
use App\Models\CouponModel;

class CouponController {

    private const CONSUME_ERRORS = [
        'notFound'    => 'Ce code de coupon n\'existe pas.',
        'ownCoupon'   => 'Tu ne peux pas utiliser tes propres coupons.',
        'alreadyUsed' => 'Tu as déjà utilisé ce coupon.',
        'exhausted'   => 'Ce coupon a atteint son nombre maximum d\'utilisations.',
        'expired'     => 'Ce coupon est expiré.',
        'unknown'     => 'Ce coupon ne peut pas être utilisé.',
    ];

    private const CREATE_ERRORS = [
        'duplicateCode' => 'Ce code est déjà utilisé par un autre coupon.',
    ];

    public function create(): void {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $twitchId = Session::get('user_id');

        $value     = (int) ($input['value'] ?? 0);
        $uses      = (int) ($input['uses'] ?? 1);
        $code      = $input['code'] ?? null;
        $daysValid = (int) ($input['daysValid'] ?? 14);

        if ($value <= 0) {
            http_response_code(400);
            echo json_encode(["success" => false, 'reason' => 'La valeur du coupon doit être supérieure à 0.']);
            return;
        }
        if ($uses <= 0) {
            http_response_code(400);
            echo json_encode(["success" => false, 'reason' => 'Le nombre d\'utilisations doit être supérieur à 0.']);
            return;
        }
        if ($daysValid <= 0) {
            http_response_code(400);
            echo json_encode(["success" => false, 'reason' => 'La durée de validité doit être supérieure à 0.']);
            return;
        }

        $userModel   = new UserModel();
        $couponModel = new CouponModel();

        try {
            $newTicketsCount = $userModel->incTicketsByTwitchId($twitchId, -$value * $uses);
        } catch (\RuntimeException $e) {
            http_response_code(400);
            echo json_encode(["success" => false, 'reason' => 'Tu n\'as pas assez de tickets pour créer ce coupon.']);
            return;
        }

        $result = $couponModel->create($twitchId, $value, $uses, $code, $daysValid);

        if (isset($result['error'])) {
            // Remboursement des tickets si la création échoue après déduction
            $userModel->incTicketsByTwitchId($twitchId, $value * $uses);
            http_response_code(400);
            echo json_encode(["success" => false, 'reason' => self::CREATE_ERRORS[$result['error']] ?? 'Une erreur est survenue.']);
            return;
        }

        if ($result) {
            Session::set('user_tickets', (string) ($newTicketsCount ?? 0));
            http_response_code(200);
            echo json_encode(["success" => true, 'coupon' => $result, 'newTicketsCount' => $newTicketsCount]);
        } else {
            $userModel->incTicketsByTwitchId($twitchId, $value * $uses);
            http_response_code(500);
            echo json_encode(["success" => false, 'reason' => 'Une erreur interne est survenue, tes tickets n\'ont pas été débités.']);
        }
    }

    public function delete(): void {
        header('Content-Type: application/json');
        $input    = json_decode(file_get_contents('php://input'), true);
        $twitchId = Session::get('user_id');
        $code     = $input['code'] ?? null;

        if (!$code) {
            http_response_code(400);
            echo json_encode(["success" => false, 'reason' => 'Code manquant.']);
            return;
        }

        $userModel   = new UserModel();
        $couponModel = new CouponModel();

        $coupon = $couponModel->findByCode($code);
        if (!$coupon || $coupon['createdBy'] !== $twitchId) {
            http_response_code(404);
            echo json_encode(["success" => false, 'reason' => 'Coupon introuvable.']);
            return;
        }

        try {
            $newTicketsCount = $userModel->incTicketsByTwitchId($twitchId, $coupon['value'] * $coupon['remainingUses']);
        } catch (\RuntimeException $e) {
            http_response_code(400);
            echo json_encode(["success" => false, 'reason' => $e->getMessage()]);
            return;
        }

        $deleted = $couponModel->delete($twitchId, $code);
        if ($deleted) {
            Session::set('user_tickets', (string) ($newTicketsCount ?? 0));
            http_response_code(200);
            echo json_encode(["success" => true, 'newTicketsCount' => $newTicketsCount]);
        } else {
            http_response_code(404);
            echo json_encode(["success" => false, 'reason' => 'Coupon introuvable.']);
        }
    }

    public function consume(): void {
        header('Content-Type: application/json');
        $input    = json_decode(file_get_contents('php://input'), true);
        $twitchId = Session::get('user_id');
        $code     = $input['code'] ?? null;

        if (!$code) {
            http_response_code(400);
            echo json_encode(["success" => false, 'reason' => 'Code manquant.']);
            return;
        }

        $userModel   = new UserModel();
        $couponModel = new CouponModel();

        $result = $couponModel->consume($twitchId, $code);

        if (isset($result['error'])) {
            http_response_code(400);
            echo json_encode(["success" => false, 'reason' => self::CONSUME_ERRORS[$result['error']] ?? 'Une erreur est survenue.']);
            return;
        }

        $coupon          = $result['coupon'];
        $newTicketsCount = $userModel->incTicketsByTwitchId($twitchId, $coupon['value']);

        Session::set('user_tickets', (string) ($newTicketsCount ?? 0));
        http_response_code(200);
        echo json_encode(["success" => true, 'newTicketsCount' => $newTicketsCount]);
    }

    public function list(): void {
        header('Content-Type: application/json');
        $twitchId = Session::get('user_id');

        $couponModel = new CouponModel();
        $coupons     = $couponModel->findAllByTwitchId($twitchId);

        http_response_code(200);
        echo json_encode(["success" => true, 'coupons' => $coupons]);
    }
}