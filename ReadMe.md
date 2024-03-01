## Платіжний модуль сервісу TRANZZO для CMS WordPress + WooCommerce

### Установка
1. Перший метод - установка через FTP клієнт:
    1. Завантажити папку плагіна **tranzzo_gateway** на сервер сайту до папки **[корінь сайту]/wp-content/plugins**
    2. Зайти до розділу меню **"Плагіни"** -> **"Встановлені"**, знайти у списку плагін **"Платіжний шлюз"** та натиснути **"Активувати".**
2. Другий спосіб - встановлення через адмін. панель WordPress.
    1. Завантажити папку **tranzzo_gateway** з репозиторію GitHub
    2. Заархівувати папку **tranzzo_gateway** в форматі .zip
    3. Перейти в адмін. панель WordPress в розділ **"Плагіни"** -> натиснути **"Додати новий"** та натиснути **"Завантажити плагін"**
    -> далі потрібно обрати створений архів **tranzzo_gateway.zip**
    4. Після установки, в розділі **"Плагіни"** -> **"Встановлені"**, знайдіть та активуйте плагін **"Платіжний шлюз"**.
### Налаштування
1. Отримайте ключі авторизації та ідентифікації сервісу TRANZZO (**POS_ID, API_KEY, API_SECRET, ENDPOINTS_KEY**).
2. Відкрити сторінку налаштувань WooCommerce, перейти у вкладку **"Платежі"** та натиснути **"TRANZZO"**.
3. Заповнити всі поля налаштувань платіжного шлюзу **"TRANZZO"**.
    - **Увімкнено / Вимкнено** - Увімкнути/вимкнути платіжний шлюз із доступних методів оплати
    - **Тестовий режим** - Увімкнути/вимкнути тестовий режим, при якому всі транзакції відбуваються у тестовій валюті **"XTS"**
    та не списуються реальні кошти.
    - **Заголовок** - Заголовок, що відображається на сторінці оформлення замовлення
    - **Опис** - Опис, який відображається в процесі вибору форми оплати
    - **Холдування коштів** -  Увімкнути/вимкнути метод двостадійної оплати. Це платіжний процес, що передбачає тимчасове 
    резервування (холдування) коштів клієнта на банківському рахунку для подальшого проведення платежу.
    - **Статус успішного платежу** - Після успішної оплати встановіть поточний статус замовлення WooCommerce

## Payment module for TRANZZO service for CMS WordPress + WooCommerce

### Installation
1. **First method** - installation via FTP client:
    1. Download the plugin folder **tranzzo_gateway** to the server of your website into the folder **[root of the website]/wp-content/plugins**.
    2. Go to the "Plugins" -> "Installed" section in the menu, find the plugin "Payment Gateway" in the list, and click "Activate".
2. **Second method** - installation via WordPress admin panel:
    1. Download the folder **tranzzo_gateway** from the GitHub repository.
    2. Archive the folder **tranzzo_gateway** in .zip format.
    3. Go to the WordPress admin panel in the **"Plugins"** section -> click **"Add new"** and then **"Upload plugin"** -> then choose the created archive **tranzzo_gateway.zip**.
    4. After installation, in the **"Plugins"** -> **"Installed"** section, find and activate the **"Payment Gateway"** plugin.

### Configuration
1. Get the authentication and identification keys for the TRANZZO service (**POS_ID, API_KEY, API_SECRET, ENDPOINTS_KEY**).
2. Open the WooCommerce settings page, go to the **"Payments"** tab, and click **"TRANZZO"**.
3. Fill in all the settings fields of the **"TRANZZO"** payment gateway:
    - **Enabled / Disabled** - Enable/disable the payment gateway from the available payment methods.
    - **Test mode** - Enable/disable the test mode, where all transactions occur in the test currency **"XTS"** and no real funds are deducted.
    - **Title** - Title displayed on the checkout page.
    - **Description** - Description displayed during the payment method selection process.
    - **Payment with preauthorization** - Enable/disable the two-step payment method. This is a payment process that involves temporarily reserving (holding) the customer's funds in a bank account for further payment processing.
    - **Successful payment status** - After successful payment, set the current status of the WooCommerce order.
