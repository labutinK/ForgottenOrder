<?php
use Bitrix\Iblock\ElementTable;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Sale\Basket;
use Local\HlBlockUtils\ForgottenOrder;
use Bitrix\Main\Diag\Debug;

function sendForgotten()
{
    Loader::includeModule('sale');

    $forgottenOrderApi = new ForgottenOrder();

    $cutoffDate = (new Main\Type\DateTime())->add('-1 hour');

    $forgottenOrders = $forgottenOrderApi->getByFilter([
        'UF_LETTER_SENT' => false,
        'UF_ORDER_FORGOTTEN' => true,
        '<=UF_DATE' => $cutoffDate->format('d.m.Y H:i:s')
    ]);

    foreach ($forgottenOrders as $forgottenOrder) {
        $orderId = $forgottenOrder['UF_ORDER_ID'];
        if (!$orderId) {
            continue;
        }

        $order = CSaleOrder::GetByID($orderId);
        if (!$order) {
            continue;
        }

        $userId = $order['USER_ID'];
        if (!$userId) {
            continue;
        }

        $rsUser = CUser::GetList(
            $by = "ID",
            $orderDir = "ASC",
            ['ID' => $userId],
            ['SELECT' => ['EMAIL', 'NAME', 'LAST_NAME']]
        );
        $user = $rsUser->Fetch();
        if (!$user) {
            continue;
        }

        $email = $user['EMAIL'];
        $name = trim($user['LAST_NAME'] . ' ' . $user['NAME']);

        $basketItems = CSaleBasket::GetList(
            [],
            ["ORDER_ID" => $orderId],
            false,
            false,
            ["PRODUCT_ID", "NAME", "PRICE", "QUANTITY"]
        );

        $itemsList = '';
        $ids = [];

        while ($item = $basketItems->Fetch()) {
            $itemId = $item['PRODUCT_ID'];
            $itemTotal = $item['PRICE'] * $item['QUANTITY'];
            $photoSrc = '';
            $ids[] = $itemId;

            $element = ElementTable::getList([
                'filter' => ['ID' => $itemId],
                'select' => ['ID', 'NAME', 'PREVIEW_PICTURE']
            ])->fetch();

            if (!empty($element['PREVIEW_PICTURE'])) {
                $resizedImage = CFile::ResizeImageGet(
                    $element['PREVIEW_PICTURE'],
                    ['width' => 200, 'height' => 200],
                    BX_RESIZE_IMAGE_EXACT,
                    true
                );
                $photoSrc = $resizedImage['src'] ?? '';
            }

            $itemsList .= '<tr>
                    <td align="center">
                        <table border="0" cellspacing="0" cellpadding="0" width="560" border-collapse="collapse" role="presentation" style="width: 560px">
                            <tr>
                                <td style="padding: 0 0 0 0" >
                                    <table border="0" cellspacing="0" cellpadding="0" width="232" border-collapse="collapse" role="presentation" style="width: 232px;">
                                        <tr>
                                            <td align="left">
                                                <img src="' . $photoSrc . '" width="212" height="212" alt="product image" style="width: 212px; height: 212px; display: block" border="0">
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <!--item description-->
                                <td align="left" valign="top" style="border-bottom: 1px solid #000000">
                                    <table border="0" cellspacing="0" cellpadding="0" width="328" border-collapse="collapse" role="presentation" style="width: 328px;">
                                        <tr>
                                            <td valign="top">
                                                <table border="0" cellspacing="0" cellpadding="0" width="100%" border-collapse="collapse" role="presentation" style="width: 100%;">
                                                    <tr>
                                                        <td align="left" width="200" style="line-height: 10px;">
                                                            <span class="cards-name" style="font-family: `Arial`, sans-serif; font-size: 14px; line-height: 14px; font-weight: 400; color:#000000; text-align: left;">'
                . $item['NAME'] .
                '</span>
                                                        </td>
                                                        <td align="right">
                                                            <span class="cards-price" style="font-family: `Arial`, sans-serif; font-size: 24px; line-height: 24px; font-weight: 400; color:#000000; text-align: right;">
                                                                ' . $itemTotal . '₽
                                                            </span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <!--padding 24--><tr>
                                        <td align="center" bgcolor="" style="padding:0 0 0 0">
                                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-spacing:0px;max-width:600px">
                                                <tbody>
                                                <tr>
                                                    <td align="center" height="12" style="font-size:0;height:12px;line-height:0"> </td>
                                                </tr>
                                                <tr>
                                                    <td align="center" height="12" style="font-size:0;height:12px;line-height:0"> </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                        <tr>
                                            <td align="left" style="line-height: 10px;">
                                                <span class="cards-description" style="font-family: `Arial`, sans-serif; font-size: 14px; line-height: 14px; font-weight: 400; color:#000000; text-align: left;">
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <!--padding 20--><tr>
                <td align="center" bgcolor="" style="padding:0 0 0 0">
                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-spacing:0px;max-width:600px">
                        <tbody>
                        <tr>
                            <td align="center" height="20" style="font-size:0;height:20px;line-height:0"> </td>
                        </tr>
                        </tbody>
                    </table>
                </td>
                </tr>';
        }

        if (empty($ids)) {
            Debug::writeToFile("корзина заказа $orderId пуста", "sendForgotten", "/send-forgotten-order.log");
            continue;
        }

        $arEventFields = [
            'USER' => $name,
            'PRICE' => $order['PRICE'],
            'EMAIL' => $email,
            'ITEMS' => $itemsList,
        ];

        CEvent::Send("FORGOTTEN_ORDER", 's1', $arEventFields);

        $forgottenOrderApi->update($forgottenOrder['ID'], [
            'UF_ORDER_FORGOTTEN' => false,
            'UF_DATE' => new Main\Type\DateTime(),
            'UF_LETTER_SENT' => true,
        ]);
    }

    return "sendForgotten();";
}
