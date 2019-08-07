<?php

namespace Bitrix\OasisImporter;

use Bitrix\Main\SystemException;

/**
 * Class Api
 * @package Bitrix\OasisImporter
 */
class Api
{
    private $moduleId = 'oasis_importer';
    private $moduleVersion = '1.0.0';
    private $apiUrl = 'https://api.oasiscatalog.com/v4/';
    private $key = '';

    /**
     * Api constructor.
     * @param $key
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * @return bool|mixed
     * @throws SystemException
     */
    public function getCategories()
    {
        return $this->request('categories', []);
    }

    /**
     * @param $categories
     * @return bool|mixed
     * @throws SystemException
     */
    public function getProductByCategory($categories, $full = false)
    {
        return $this->request(
            'products',
            ['category' => implode(",", $categories), 'fieldset' => ($full ? 'full' : 'basic')]
        );
    }

    /**
     * @param $category
     * @return bool|mixed
     * @throws SystemException
     */
    public function getProductCountByCategory($category)
    {
        $data = $this->request(
            'stat',
            ['category' => $category]
        );

        return $data;
    }

    /**
     * @param $method
     * @param array $params
     * @return bool|mixed|null
     * @throws SystemException
     */
    public function request($method, $params = [])
    {
        if (!$this->key) {
            return null;
        }

        $data = false;
        try {
            $url = $this->apiUrl . $method;
            $params['plugin'] = 'bitrix';
            $params['version'] = $this->moduleVersion;
            $params['key'] = $this->key;
            if (!isset($params['format'])) {
                $params['format'] = 'json';
            }
            if ($params) {
                $url .= '?' . http_build_query($params);
            }
            $dataRes = file_get_contents($url);
            $data = json_decode($dataRes, true);
        } catch (\Exception $e) {
            throw new SystemException('Ошибка запроса к API oasiscatalog.com');
        }
        return $data;
    }
}
