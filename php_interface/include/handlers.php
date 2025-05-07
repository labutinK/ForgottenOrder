<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Sale;
use Local\HlBlockUtils\ForgottenOrder;

Main\EventManager::getInstance()->addEventHandler("sale", "OnOrderUpdate", [OrderEventHandler::class, 'orderUpdateHandler']);
Main\EventManager::getInstance()->addEventHandler("sale", "OnOrderAdd", [OrderEventHandler::class, 'orderAddHandler']);

class OrderEventHandler
{
    public static function orderAddHandler(int $orderId, array $arFields): void
    {
        self::forgottenOrderController($arFields['ID'], true);
    }

    public static function orderUpdateHandler(int $orderId, array &$arFields): void
    {
        $order = \Bitrix\Sale\Order::load($orderId);
        if ($order) {

            $successfulStatuses = ['P', 'OT'];
            if (in_array($arFields['STATUS_ID'], $successfulStatuses, true)) {
                self::forgottenOrderController($arFields['ID'], false);
            }

        }
    }

    private static function forgottenOrderController(?int $orderId, ?bool $forgottenOrderStatus = null): void
    {
        if (Loader::includeModule('sale')) {

            $forgottenOrderApi = new ForgottenOrder();
            $fUser = \Bitrix\Sale\Fuser::getId();

            $fuserExist = $forgottenOrderApi->getByFilter(
                array(
                    'UF_FUSER' => intval($fUser),
                    'UF_ORDER_ID' => intval($orderId)
                )
            );

            if ($fuserExist && (count($fuserExist) > 0)) {
                $date = new \Bitrix\Main\Type\DateTime();
                $forgottenOrderApi->update(
                    $fuserExist[0]['ID'],
                    array(
                        'UF_DATE' => $date->format('d.m.Y H:i:s'),
                        'UF_ORDER_FORGOTTEN' => $forgottenOrderStatus,
                        'UF_LETTER_SENT' => false,
                    )
                );
            } else {
                $forgottenOrderApi->add(
                    array(
                        'UF_FUSER' => $fUser,
                        'UF_ORDER_FORGOTTEN' => $forgottenOrderStatus,
                        'UF_ORDER_ID' => intval($orderId),
                        'UF_LETTER_SENT' => false,
                    )
                );
            }
        }
    }
}
