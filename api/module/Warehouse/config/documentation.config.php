<?php
return array(
    'Warehouse\\V1\\Rest\\Histories\\Controller' => array(
        'collection' => array(
            'GET' => array(
                'response' => '{
    "_links": {
        "self": {
            "href": "https://earth.api.ginosi.com/warehouse/asset/1/histories"
        }
    },
    "_embedded": {
        "histories": [
            {
                "date": "2015-09-15 13:20:27",
                "desription": "Arbi Baghoomian  had changed from <b>Storage: Chicago Storage</b> to <b>Office: LA Hollywood Office</b>.",
                "userName": "Arbi Baghoomian"
            },
            {
                "date": "2015-09-15 13:18:50",
                "desription": "Arbi Baghoomian  had changed from <b>Apartment: Bella Vista</b> to <b>Storage: Chicago Storage</b>.",
                "userName": "Arbi Baghoomian"
            },
            {
                "date": "2015-09-15 13:13:48",
                "desription": "Arbi Baghoomian had changed <b>status</b> from <b>Broken</b> to <b>Retired</b> for following reason: ",
                "userName": "Arbi Baghoomian"
            },
            {
                "date": "2015-09-15 13:11:36",
                "desription": "Arbi Baghoomian had changed <b>status</b> from <b></b> to <b>Working</b> for following reason: ",
                "userName": "Arbi Baghoomian"
            },
            {
                "date": "2015-09-15 12:56:16",
                "desription": "Arbi Baghoomian had changed <b>assignee</b> from <b>Hovig Zoubrigian</b> to <b>Arbi Baghoomian</b>",
                "userName": "Arbi Baghoomian"
            },
            {
                "date": "2015-09-15 12:56:16",
                "desription": "Arbi Baghoomian had changed <b>status</b> from <b>Working</b> to <b></b> for following reason: ",
                "userName": "Arbi Baghoomian"
            }
        ]
    },
    "total_items": 6
}',
            ),
        ),
    ),
    'Warehouse\\V1\\Rest\\Categories\\Controller' => array(
        'collection' => array(
            'GET' => array(
                'response' => '<b>Type 1 : Consumable</b>
<b>Type 2: Valuable</b> 

{
    "categories": [
        {
            "id": "1",
            "name": "Toilet Paper",
            "type": "1",
            "inactive": "0"
        },
        {
            "id": "2",
            "name": "Computer",
            "type": "2",
            "inactive": "0"
        }
    ]
}',
                'description' => '',
            ),
        ),
    ),
    'Warehouse\\V1\\Rest\\Locations\\Controller' => array(
        'collection' => array(
            'description' => '',
            'GET' => array(
                'response' => '"locations": {
        "apartments": [
            {
                "id": "42",
                "name": "Test Apartment n1",
                "address": "104 Andranik st",
                "buildingId": "1",
                "cityId": "6",
                "locationType": "1"
            }
        ],
        "buildings": [
            {
                "id": "1",
                "name": "Test Apartment Group",
                "countryId": "2",
                "locationType": "4"
            }
         ],
         "storages": [
            {
                "id": "3",
                "name": "Chicago Storage",
                "locationType": "2"
            }
         ],
         "offices": [
            {
                "id": "1",
                "name": "Yerevan Office",
                "description": "Head office of Ginosi located in Yerevan city",
                "address": "K. Ulnetsi 31",
                "countryId": "2",
                "cityId": "6",
                "locationType": "3"
            }
        ]
}',
            ),
        ),
    ),
    'Warehouse\\V1\\Rest\\Barcodes\\Controller' => array(
        'collection' => array(
            'GET' => array(
                'response' => '<b>IF barcode is valuable:</b>
{
    "valuableInfo": {
        "id": "4",
        "status": "6",
        "categoryId": "2",
        "locationEntityId": "3",
        "locationEntityType": "2",
        "serialNumber": "erterertre",
        "locationName": "Chicago Storage",
        "categoryName": "Computer",
        "firstnameLastUpdated": "Arbi",
        "lastnameLastUpdated": "Baghoomian",
        "statusName": "New",
        "history": {
            "date": "2015-09-10 15:28:55",
            "desription": "Arbi Baghoomian  had changed from <b>Storage: yerevan</b> to <b>Storage: Chicago Storage</b>.",
            "userName": "Arbi Baghoomian"
        }
    },
    "consumableInfo": false
}

<b>IF barcode is consumable:</b>
{
    "valuableInfo": false,
    "consumableInfo": {
        "id": "1",
        "categoryId": "1",
        "locationEntityId": "3",
        "locationEntityType": "2",
        "quantity": "45020",
        "description": "",
        "locationName": "Chicago Storage",
        "skuName": "hoho",
        "skuId": "3",
        "categoryName": "Toilet Paper",
        "firstnameLastUpdated": "Arbi",
        "lastnameLastUpdated": "Baghoomian"
    }
}',
            ),
        ),
    ),
    'Warehouse\\V1\\Rest\\Users\\Controller' => array(
        'collection' => array(
            'GET' => array(
                'response' => '{
    "users": [
        {
            "id": "12294",
            "firstname": "App",
            "lastname": "user"
        }
}',
            ),
        ),
    ),
    'Warehouse\\V1\\Rest\\Auth\\Controller' => array(
        'collection' => array(
            'GET' => array(
                'response' => '<b>action: login</b>
<b>If user does not have permission assetInfo and User list will be empty AND globalPermission value will be false</b>
{
    "userInfo": {
        "id": "234",
        "firstname": "Arbi",
        "lastname": "Baghoomian",
        "email": "arbi.baghoomian@ginosi.com",
        "cityId": "6",
        "countryId": "2",
        "permissions": [
            53,
            54
        ]
    },
    "locations": {
        "apartments": [
            {
                "id": "42",
                "name": "Test Apartment n1",
                "address": "104 Andranik st",
                "buildingId": "1",
                "cityId": "6",
                "locationType": "1"
            }
        ],
        "buildings": [
            {
                "id": "1",
                "name": "Test Apartment Group",
                "countryId": "2",
                "locationType": "4"
            }
         ],
         "storages": [
            {
                "id": "3",
                "name": "Chicago Storage",
                "locationType": "2"
            }
         ],
         "offices": [
            {
                "id": "1",
                "name": "Yerevan Office",
                "description": "Head office of Ginosi located in Yerevan city",
                "address": "K. Ulnetsi 31",
                "countryId": "2",
                "cityId": "6",
                "locationType": "3"
            }
        ]
    },
    "assetValuables": [
             {
                "id": "4",
                "name": "testooo",
                "categoryId": "2",
                "locationEntityId": "3",
                "locationEntityType": "2",
                "serialNumber": "5dsf4g35d4f3sd4f3sd4f34s",
                "categoryName": "Computer",
                "statusName": "New",
                "asigneeFirstname": "App",
                "asigneeLastname": "user",
                "locationName": "Chicago Storage"
            }
        ],
        "assetConsumables": [
            {
                "id": "1",
                "description": "For WC :)~",
                "quantity": "450",
                "lastUpdatedById": "234",
                "shipmentStatus": "1",
                "locationEntityId": "3",
                "locationEntityType": "2",
                "categoryId": "1",
                "skuNames": [
                    "5454534544354",
                    "54354354543544",
                    "fdssdf4sd54f5ds33"
                ],
                "categoryName": "Toilet Paper",
                "locationName": "Chicago Storage"
            }
        ],
    "users": [
       {
            "id": "12294",
            "firstname": "App",
            "lastname": "user"
        }
     ],
    "globalPermission": true
}',
            ),
        ),
    ),
    'Warehouse\\V1\\Rest\\Assets\\Controller' => array(
        'collection' => array(
            'GET' => array(
                'response' => '{
    "assetsInfo": {
        "assetValuables": [
                       {
                "id": "4",
                "name": "testooo",
                "categoryId": "2",
                "locationEntityId": "3",
                "locationEntityType": "2",
                "serialNumber": "5dsf4g35d4f3sd4f3sd4f34s",
                "categoryName": "Computer",
                "statusName": "New",
                "asigneeFirstname": "App",
                "asigneeLastname": "user",
                "locationName": "Chicago Storage"
            }
        ],
        "assetConsumables": [
            {
                "id": "1",
                "description": "For WC :)~",
                "quantity": "450",
                "lastUpdatedById": "234",
                "shipmentStatus": "1",
                "locationEntityId": "3",
                "locationEntityType": "2",
                "categoryId": "1",
                "skuNames": [
                    "5454534544354",
                    "54354354543544",
                    "fdssdf4sd54f5ds33"
                ],
                "categoryName": "Toilet Paper",
                "locationName": "Chicago Storage"
            }
        ]
    }
}',
                'description' => '',
            ),
            'description' => '',
        ),
        'description' => 'kokoko',
    ),
);
