<?php

final class XMLDocForRosreestr
{
    private static $doc;
    private static $eDoc;
    private static $req;
    private static $reqData;
    private static $reqDataReal;
    private static $extraRealty;
    private static $root;
    private static $loc;
    private static $object;
    private static $objects;
    private static $address;
    private static $data;
    private static $guid;

    private static $docType = [
        1 => 'ExtractRealtyList',
        'ExtractRealty'
    ];

    private static $cadastr = [
        1 => 'CadastralNumber',
        'ConditionalCadastralNumber'
    ];

    private static $objectType = [
        'жилой дом' => [
            'Building',
            'IsLiving',
            'true'
        ],
        'здание (нежилое)' => [
            'Building',
            'IsNondomestic',
            'true'
        ],
        'земельный участок' => false,
        'квартира' => [
            'Room',
            'IsFlat',
            'true'
        ],
        'комната' => [
            'Room',
            'IsRoom',
            'true'
        ],
        'объект незавершённого строительства' => [
            'Obj_Kind',
            '002001005000'
        ],
        'помещение (нежилое)' => [
            'Room',
            'IsNondomestic',
            'true'
        ],
        'сооружение' => [
            'Obj_Kind',
            '002001004000'
        ],
        'участок недр' => [
            'Obj_Kind',
            '002001007000'
        ]
    ];

    public static function generate($filename, $data)
    {
        self::$data = $data;
        self::construct();
        self::createEDoc();
        self::createBasicTags();

        if (self::$data['object_type'] == 'участок недр' || self::$data['object_type'] == 'земельный участок') {
            self::createParcel();
            self::createLocation(self::$address);
        }

        if (self::$data['object_type'] != 'земельный участок') {
            self::createObject();
        }

        self::$doc->save($filename . '.xml');
    }

    public static function construct()
    {
        self::$guid = self::getGUID();
        self::$doc = new DOMDocument('1.0', 'utf-8');
        self::$doc->xmlStandalone = true;
        self::$doc->formatOutput = true;
        self::$root = self::$doc->createElement('RequestGRP');
        self::$doc->appendChild(self::$root);
    }

    public static function createEDoc()
    {
        self::$eDoc = self::$doc->createElement('eDocument');
        self::$eDoc->setAttribute('Version', '2.0');
        self::$eDoc->setAttribute('GUID', self::$guid);
        self::$eDoc->setAttribute('SubVersion', '2.10');
        self::$root->appendChild(self::$eDoc);
    }

    public static function createBasicTags()
    {
        self::$req = self::$doc->createElement('Request');
        self::$root->appendChild(self::$req);

        self::$reqData = self::$doc->createElement('RequiredData');
        self::$req->appendChild(self::$reqData);

        self::createStaticStuff();

        self::$reqDataReal = self::$doc->createElement('RequiredDataRealty');
        self::$reqData->appendChild(self::$reqDataReal);

        self::$extraRealty = self::$doc->createElement(self::$docType[self::$data['doc_key']]);
        self::$reqDataReal->appendChild(self::$extraRealty);

        self::$objects = self::$doc->createElement('Objects');
        self::$extraRealty->appendChild(self::$objects);
    }

    private static function createLocation($el)
    {
        self::$loc = self::$doc->createElement('Location');
        $el->appendChild(self::$loc);

        self::createRegionTag('Code_OKATO');
        self::createRegionTag('Region');
        self::createRegionTag('Other');
        self::createRegionTag('Note');

        self::createAddrTag('District', 'Name');
        self::createAddrTag('City', 'Name');
        self::createAddrTag('Locality', 'Name');
        self::createAddrTag('Street', 'Name');

        self::createAddrTag('Level1', 'Value');
        self::createAddrTag('Level2', 'Value');
        self::createAddrTag('Level3', 'Value');
        self::createAddrTag('Apartment', 'Value');
    }

    private static function createRegionTag($name)
    {
        if (strlen(self::$data[$name]) > 0) {
            $el = self::$doc->createElement($name, self::$data[$name]);
            self::$loc->appendChild($el);
        }
    }

    private static function createAddrTag($name, $attr)
    {
        if (strlen(self::$data[$name]) > 0) {
            $el = self::$doc->createElement($name);
            $el->setAttribute('Type', self::$data[$name . 'Type']);
            $el->setAttribute($attr, self::$data[$name]);
            self::$loc->appendChild($el);
        }
    }

    private static function createObjKindTag()
    {
        $objectKind = self::$doc->createElement('ObjKind');
        self::$object->appendChild($objectKind);

        if (count(self::$objectType[self::$data['object_type']]) > 2) {
            $objFirstEl = self::$doc->createElement(self::$objectType[self::$data['object_type']][0]);
            $objectKind->appendChild($objFirstEl);
            $objSecondEl = self::$doc->createElement(self::$objectType[self::$data['object_type']][1], self::$objectType[self::$data['object_type']][2]);
            $objFirstEl->appendChild($objSecondEl);
        } else {
            $objFirstEl = self::$doc->createElement(self::$objectType[self::$data['object_type']][0], self::$objectType[self::$data['object_type']][1]);
            $objectKind->appendChild($objFirstEl);
        }
    }

    private static function createParcel()
    {
        $parcel = self::$doc->createElement('Parcel');
        self::$objects->appendChild($parcel);
        $desc = self::$doc->createElement('Description');
        $parcel->appendChild($desc);
        self::$address = self::$doc->createElement('Address');
        $desc->appendChild(self::$address);

        if (strlen(self::$data['CadastralNumber']) > 0) {
            $cadastralNum = self::$doc->createElement('CadastralNumber', self::$data['CadastralNumber']);
            self::$address->appendChild($cadastralNum);
        }

        if (strlen(self::$data['Value']) > 0) {
            $areas = self::$doc->createElement('Areas');
            self::$address->appendChild($areas);
            $areaVal = self::$doc->createElement('Value', self::$data['Value']);
            $areas->appendChild($areaVal);

            if (strlen(self::$data['Unit']) > 0) {
                $areaUnit = self::$doc->createElement('Unit', self::$data['Unit']);
                $areas->appendChild($areaUnit);
            }
        }
    }

    private static function createObject()
    {
        self::$object = self::$doc->createElement('Object');
        self::$objects->appendChild(self::$object);
        self::createObjKindTag();

        if (self::$data['object_type'] != 'участок недр') {
            $cadastr = self::$doc->createElement('CadastralNumbers');
            self::$object->appendChild($cadastr);

            $cadastrNum = self::$doc->createElement(self::$cadastr[self::$data['cadastr_key']], self::$data['CadastralNumber']);
            $cadastr->appendChild($cadastrNum);

            self::createLocation(self::$object);
        }
    }

    private static function createStaticStuff()
    {
        $appliedDocs = self::$doc->createElement('Applied_Documents');
        self::$req->appendChild($appliedDocs);

        $appliedDoc = self::$doc->createElement('Applied_Document');
        $appliedDocs->appendChild($appliedDoc);

        $codeDoc = self::$doc->createElement('Code_Document', '123456789012');
        $appliedDoc->appendChild($codeDoc);

        $number = self::$doc->createElement('Number', self::$guid);
        $appliedDoc->appendChild($number);

        $date = self::$doc->createElement('Date', date('Y-m-dP'));
        $appliedDoc->appendChild($date);

        $quan = self::$doc->createElement('Quantity');
        $appliedDoc->appendChild($quan);

        $original = self::$doc->createElement('Original');
        $original->setAttribute('Quantity', '1');
        $original->setAttribute('Quantity_Sheet', '1');
        $quan->appendChild($original);

        $payment = self::$doc->createElement('Payment');
        self::$req->appendChild($payment);

        $free = self::$doc->createElement('Free', 'true');
        $payment->appendChild($free);

        if (strlen(self::$data['LinkE_mail']) > 0) {
            $delivery = self::$doc->createElement('Delivery');
            self::$req->appendChild($delivery);

            $mail = self::$doc->createElement('LinkE_mail', self::$data['LinkE_mail']);
            $delivery->appendChild($mail);
        }
    }

    private static function getGUID()
    {
        mt_srand((double)microtime() * 10000);
        $charid = strtolower(md5(uniqid(rand(), true)));
        $hyphen = chr(45);
        $uuid = substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);
        return $uuid;
    }
}

$data = [
    'doc_key'           => 1,                           // 1 = Характеристики, 2 = Переход прав
    'object_type'       => 'земельный участок',         // strtolower($val)
    'cadastr_key'       => 1,                           // 1 = Кадастровый, 2 = Условный
    'CadastralNumber'   => 102030,                      // Кадастровый/Условный номер
    'Value'             => 302010,                      // Значение площади
    'Unit'              => '055',                       // Единицы измерения площади
    'Other'             => 'lorem',                     // Иное
    'Note'              => 'ipsum',                     // Иное описание местоположения
    'Code_OKATO'        => 'dolor',                     // ХЗ, как я понял в json файле это okato
    'Region'            => 'sit',                       // Номер региона
    'DistrictType'      => 'р-н',                       // По умолчанию р-н
    'District'          => 'amet',                      // Название района
    'CityType'          => 'г',                         // По умолчанию г
    'City'              => 'lorem',                     // Название города
    'LocalityType'      => 'нп',                        // По умолчанию нп
    'Locality'          => 'ipsum',                     // Название населенного пункта
    'StreetType'        => 'ул',                        // ул пр и тд
    'Street'            => 'dolor',                     // Название улицы
    'Level1Type'        => 'д',                         // д (дом)
    'Level1'            => 'sit',                       // номер дома
    'Level2Type'        => 'стр',                       // стр (строение)
    'Level2'            => 'amet',                      // обозначение строения
    'Level3Type'        => 'к',                         // к (как я понял корпус)
    'Level3'            => 'lorem',                     // номер корпуса
    'ApartmentType'     => 'кв',                        // кв
    'Apartment'         => 'ipsum',                     // номер квартиры
    'LinkE_mail'        => 'dolor@sit.amet',            // номер квартиры
];

XMLDocForRosreestr::generate('zemelniy_uch', $data);
