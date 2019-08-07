**Модуль интеграции Oasiscatalog API и 1С-Битрикс**

Модуль позволяет загрузить товары в нужные разделы каталога. Есть возможность упралвять наценкой или скидкой на каждый товар относительно РРЦ. 

**Установка**
- скопировать архив из репозитория на github.com/oasiscatalog/bx_oasis_importer
- положить файлы в директорию с модулями 1С-Битрикс /bitrix/modules/oasis_importer
- на странице настройки модуле установить модуль - http://{ваш_домен}/bitrix/admin/module_admin.php
- на странице настройки модуля (http://{ваш_домен}/bitrix/admin/settings.php?lang=ru&mid=oasis_importer&mid_menu=1) - указать API-ключ и установить другие настройки 
- на странице http://{ваш_домен}/bitrix/admin/oasis_importer_categories.php?lang=ru - сделать сопоставление разделов каталога Вашего магазина и рубрики из Oasiscatalog
- на странице http://{ваш_домен}/bitrix/admin/oasis_importer_params.php?lang=ru - сделать сопоставление параметров товара
- на главной странице модуля (http://{ваш_домен}/bitrix/admin/oasis_importer_admin.php?lang=ru) - запустить импорт товаров

**TODO**

- Сделать добавление в пользовательский справочник значений
- Сделать сопоставление и добавлени вновь добавленных значений из пользовательского спраовчника
```
//добавление к инфоблоку свойства типа "Справочник"
 $arFields = Array(
   "NAME" => "Производитель",
   "ACTIVE" => "Y",
   "SORT" => "50",
   "CODE" => "PROIZVODITEL",
   "PROPERTY_TYPE" => "S",
   "USER_TYPE" => "directory",
   "IBLOCK_ID" => 888888888888888,//номер вашего инфоблока
   "LIST_TYPE" => "L",
   "MULTIPLE" => "N",
   "USER_TYPE_SETTINGS" => array("size"=>"1", "width"=>"0", "group"=>"N", "multiple"=>"N", "TABLE_NAME"=>"b_producers")
);

$ibp = new CIBlockProperty;
$PropID = $ibp->Add($arFields);


//затем следует в значение свойства вставить значение поля  UF_XML_ID от вашего справочника
CIBlockElement::SetPropertyValuesEx(123188, 888888888888888, array('PROIZVODITEL'=>'000000701'));
```
