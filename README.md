<h1>Функционал забытой корзины для Bitrix Cms</h1>

<p>Для установки необходимо:<p>
<ol>
<li>Установить HighloadBlock (файл в папке /migrations/Version1ForgottenBasket20250507015705.php) через <a target="_blank" href="https://marketplace.1c-bitrix.ru/solutions/sprint.migration/">миграции для разрабочтиков</a>
</li>
<li>Зарегистрируйте агент sendForgotten через интерфейс контроля над агентами (на странице Список агентов (Настройки > Настройки продукта > Агенты) или через <a href="https://dev.1c-bitrix.ru/api_help/main/reference/cagent/addagent.php">CAgent::AddAgent</a></li>
</ol>

<p>Для отправки сообщение на почту необходимо наличие почтового шаблона FORGOTTEN_ORDER с переменными: USER(имя), PRICE(сумма заказа), EMAIL(почта покупателя), ITEMS(товары заказа) </p>
