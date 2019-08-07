create table if not exists b_oasis_importer_categories
(
    ID int(18) not null auto_increment,
    IBLOCK_ID int(18) not null,
    SECTION_ID int(18) not null,
    CATEGORY_ID int(18) not null,
    DATE_CREATE timestamp null,
    DATE_MODIFY timestamp not null,
    PRIMARY KEY(ID),
    INDEX IX_B_OASIS_CAT_IBLOCK_ID (IBLOCK_ID),
    INDEX IX_B_OASIS_CAT_SECTION_ID (SECTION_ID),
    INDEX IX_B_OASIS_CAT_CATEGORY_ID (CATEGORY_ID)
);

create table if not exists b_oasis_importer_products
(
    ID int(18) not null auto_increment,
    IBLOCK_ID int(18) not null,
    SECTION_ID int(18) not null,
    PRODUCT_ID int(18) not null,
    OASIS_PRODUCT_ID varchar(255) not null,
    DATE_CREATE timestamp null,
    DATE_MODIFY timestamp not null,
    PRIMARY KEY(ID),
    INDEX IX_B_OASIS_PRODUCT_IBLOCK_ID (IBLOCK_ID),
    INDEX IX_B_OASIS_PRODUCT_PRODUCT_ID (PRODUCT_ID),
    INDEX IX_B_OASIS_PRODUCT_OASIS_PRODUCT_ID (OASIS_PRODUCT_ID)
);

create table if not exists b_oasis_importer_params
(
    ID int(18) not null auto_increment,
    NAME varchar(255) not null,
    PARAMS text default null,
    CREATED_BY_ID int(18) not null,
    MODIFIED_BY_ID int(18) not null,
    DATE_CREATE timestamp null,
    DATE_MODIFY timestamp not null,
    PRIMARY KEY(ID),
    INDEX IX_B_OASIS_PARAMS_NAME (NAME)
);
