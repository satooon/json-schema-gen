<?php namespace Satooon\JsonSchemaGen;

class JsonSchemaGen
{

    private $headers;
    private $request;
    private $data;
    private $url;

    public static function make()
    {
        return new self();
    }

    public function run()
    {
        $this->render(json_decode($this->curl()));
        return $this;
    }

    public function finish($callBack)
    {
        $callBack();
    }

    public function setOption($option = [])
    {
        array_map(function ($key, $val) {
            if (property_exists($this, $key)) {
                $this->{"set".ucfirst($key)}($val);
            }
        }, array_keys($option), array_values($option));
        return $this;
    }

    public function setHeaders(array $headers)
    {
        $this->headers = self::formatHeaders($headers);
        return $this;
    }

    public static function formatHeaders(array $headers)
    {
        return array_reduce($headers, function ($formatHeaders, $str) {
            list($key, $val) = explode(':', $str);
            $formatHeaders[$key] = $val;
            return $formatHeaders;
        }, []);
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getUri()
    {
        $parse = parse_url($this->getUrl());
        return $parse['path'];
    }

    private function curl()
    {
        $curl   = new \anlutro\cURL\cURL();

        switch (true) {
            case preg_match('/post/i', $this->getRequest()):
                return $curl->newRequest(
                            'post',
                            $this->getUrl(),
                            ['payload' => json_encode(json_decode($this->getData(), true))]
                        )
                        ->setHeaders($this->getHeaders())
                        ->send()
                        ->body;
            case preg_match('/get/i', $this->getRequest()):
                return $curl->newRequest(
                            'get',
                            $this->getUrl(),
                            []
                        )
                        ->setHeaders($this->getHeaders())
                        ->send()
                        ->body;
            default:
                return $curl->newRequest(
                            $this->getRequest(),
                            $this->getUrl(),
                            []
                        )
                        ->setHeaders($this->getHeaders())
                        ->send()
                        ->body;
        }
    }

    private function render($data)
    {
        echo SchemaTemplate::make($data)
            ->setId($this->getUrl())
            ->setTitle($this->getUri())
            ->setDescription($this->getRequest() . ' ' . $this->getUri())
            ->setMethod($this->getRequest())
            ->setUrl($this->getUrl())
            ->withProperties()
            ->withLinks()
            ->toJson();
    }
}

class SchemaTemplate
{
    private $id;
    private $title;
    private $description;
    private $method;
    private $url;

    private $data;

    public $properties;
    public $links;

    const SCHEMA_DRAFT = "http://json-schema.org/draft-04/hyper-schema";

    public static function make($data = [])
    {
        $obj = new self();
        $obj->links = new \Illuminate\Support\Collection();
        return $obj->setData($data);
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function getSchemaDraft()
    {
        return self::SCHEMA_DRAFT;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function withProperties()
    {
        $propertiesCollection = \Illuminate\Support\Collection::make([]);
        $datas = is_object($this->getData()) ? get_object_vars($this->getData()) : $this->getData();

        array_map(function ($key, $data) use(&$propertiesCollection) {
            switch (true) {
                case is_array($data) && array_filter($data, function ($d) {
                    return is_array($d) || is_object($d);
                }):
                    $d  = array_shift($data);
                    $propertiesCollection->put(
                        $key,
                        PropertyCollection::make($d)
                            ->setFieldName($key)
                            ->withSchemaItemCollectionCollection()
                    );
                    break;
                default:
                    $propertiesCollection->put(
                        $key,
                        PropertyItem::make($data)
                            ->setFieldName($key)
                            ->settingData()
                    );
                    break;
            }
        }, array_keys($datas), array_values($datas));

        $this->properties = $propertiesCollection;
        return $this;
    }

    public function withLinks()
    {
        $this->links->push([
            'title' => 'List',
            'description' => 'List ' . $this->getTitle(),
            'method' => $this->getMethod(),
            'href' => $this->getUrl(),
            'rel' => 'self',
        ]);

        return $this;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function toJson()
    {
        $data   = [
            '$schema'       => $this->getSchemaDraft(),
            'id'            => $this->getId(),
            'title'         => $this->getTitle(),
            'description'   => $this->getDescription(),
            'properties'    => [],
            'links'         => $this->links,
            'required'      => [],
            'type'          => 'object',
        ];

        $this->getProperties()->each(function ($properties) use(&$data) {
            $data['properties'][$properties->getFieldName()] = $properties->toArray();
            $data['required'][] = $properties->getFieldName();
        });

        return json_encode($data, JSON_PRETTY_PRINT);
    }
}

class PropertyCollection extends \Illuminate\Support\Collection
{
    private $fieldName;
    private $type = 'array';
    private $data;

    public $properties;

    public function toArray()
    {
        $this->put('type', $this->getType());
        $this->put('items', [
            'type'       => 'object',
            'properties' => $this->properties->toArray(),
        ]);

        return parent::toArray();
    }

    public static function make($data = [])
    {
        $obj = parent::make([]);
        $obj->properties = new \Illuminate\Support\Collection();
        return $obj->setData($data);
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function getFieldName()
    {
        return $this->fieldName;
    }

    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function withSchemaItemCollectionCollection()
    {
        $datas = is_object($this->getData()) ? get_object_vars($this->getData()) : $this->getData();
        $datas = is_array($datas) ? $datas : [$datas];

        array_map(function ($key, $data) {
            switch (true) {
                case is_array($data) && array_filter($data, function ($d) {
                    return is_array($d) || is_object($d);
                }):
                case is_object($data) && array_filter((array)$data, function ($d) {
                    return is_array($d) || is_object($d);
                }):
                    $this->properties->put(
                        $key,
                        PropertyCollection::make($data)
                            ->setFieldName($key)
                            ->setType(is_object($data) ? SchemaType::OBJECT_TYPE : SchemaType::ARRAY_TYPE)
                            ->withSchemaItemCollectionCollection()
                    );
                    break;
                default:
                    $this->properties->put(
                        $key,
                        PropertyItem::make($data)
                            ->setFieldName($key)
                            ->settingData()
                    );
                    break;
            }
        }, array_keys($datas), array_values($datas));

        return $this;
    }
}

class PropertyItem extends \Illuminate\Support\Collection
{
    private $fieldName;
    private $description;
    private $example;
    private $type;
    private $format;
    private $ref;

    private $data;

    public function toArray()
    {
        $this->put('description', (string) $this->getDescription());
        $this->put('example', $this->getExample());
        $this->put('type', $this->getType());

        if ($this->getType() == SchemaType::ARRAY_TYPE) {
            $this->put('items', (object) ['type' => array_unique(array_reduce($this->getData(), function ($carry, $data) {
                $carry[] = $this->checkType($data, '');
                return $carry;
            }, []))]);
        }

        return parent::toArray();
    }

    public static function make($data = [])
    {
        $obj = new self();
        return $obj->setData($data);
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function getFieldName()
    {
        return $this->fieldName;
    }

    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function getExample()
    {
        return $this->example;
    }

    public function setExample($example)
    {
        $this->example = $example;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }

    public function getRef()
    {
        return $this->ref;
    }

    public function setRef($ref)
    {
        $this->ref = $ref;
        return $this;
    }

    public function settingData()
    {
        return $this->_settingType($this->getData(), $this->getFieldName())
            ->_settingFormat()
            ->setDescription($this->getFieldName())
            ->setExample($this->getData());
    }

    private function _settingType($data, $fieldName)
    {
        $this->setType($this->checkType($data, $fieldName));
        return $this;
    }

    private function checkType($data, $fieldName)
    {
        switch (true) {
            case is_integer($data):
                return SchemaType::INT_TYPE;
            case is_numeric($data):
                return SchemaType::NUMBER_TYPE;
            case is_object($data):
                return SchemaType::OBJECT_TYPE;
            case is_bool($data):
                return SchemaType::BOOL_TYPE;
            case preg_match('/(created_at|updated_at|deleted_at)/i', $fieldName):
                return SchemaType::STRING_TYPE;
            case is_array($data):
                return SchemaType::ARRAY_TYPE;
            case is_object($data):
                return SchemaType::OBJECT_TYPE;
            default:
                return SchemaType::STRING_TYPE;
        }
    }

    private function _settingFormat()
    {
        switch (true) {
            case preg_match('/(created_at|updated_at|deleted_at)/i', $this->getFieldName()):
                $this->setFormat(SchemaFormat::DATE_TIME);
                break;
        }
        return $this;
    }
}

class SchemaType
{
    const ARRAY_TYPE  = 'array';

    const BOOL_TYPE   = 'boolean';

    const INT_TYPE    = 'integer';

    const NUMBER_TYPE = 'number';

    const NULL_TYPE   = 'null';

    const OBJECT_TYPE = 'object';

    const STRING_TYPE = 'string';
}

class SchemaFormat
{
    const DATE_TIME  = 'date-time';

    const DATE       = 'date';

    const TIME       = 'time';

    const UTC_MILLISEC = 'utc-millisec';

    const REGEX   = 'regex';

    const COLOR = 'color';

    const STYLE = 'style';

    const PHONE = 'phone';

    const URI = 'uri';

    const EMAIL  = 'email';

    const IP_V4  = 'ip-address';

    const IP_V6  = 'ipv6';

    const HOST_NAME  = 'host-name';
}
