<?php

class DsagController extends BaseController {

    protected $childrenName = 'children';
    protected $parentIdName = 'parent_id';
    protected $idName  = 'id';

    /////////////////////////
    //// Public interface
    public function getMessageList($params = array()) {

        $messages = $this->loadMessages();

        if(empty($this->params[0]) ||
                 $this->params[0] == 1) return $messages;

        $items = $rootCategory = array();
        $categoryId   = $this->params[0];
        $categoryList = $this->loadCategoryList();
        foreach ($categoryList as $key => $catItem) {
            if($catItem['id'] == $categoryId) {
                $rootCategory = $catItem;
                break;
            }
        }
        $categoryList = $this->selectedCategory($categoryId, $categoryList);
        $categoryList[] = $rootCategory;
        $catIdName = 'cat_id';
        foreach ($categoryList as $i => $category) {
            $catId = $category['id'];
            foreach ($messages as $key => $item) {
                if($item[$catIdName] == $catId)
                    $items[] = $item;
            }
        }
        return $items;
    }

    protected function selectedCategory($catId, $categoryList) {
        $result = array();
        $idName = 'id';
        $parent = 'parent_id';
        $funcName  = __FUNCTION__;
        foreach ($categoryList as $key => $item) {
            if($item[$parent] == $catId) {
                $sub = array();
                $result[] = $item;
                $id = $item[$idName];
                $sub = $this->$funcName($id, $categoryList);
                $result = array_merge($result, $sub);
            }
        }
        return $result;
    }

    public function getPerformsData() {
        $messageId = 0;
        $result = array();
        if(!empty($this->params[0]))
            $messageId = $this->params[0];
        $items = $this->loadPerformsData();
        if($messageId) {
            foreach ($items as $key => $value) {
                if($value['mess_id'] == $messageId) {
                    $result[] = $value;
                }
            }
        } else {
            $result = $items;
        }
        return $result;
    }

    public function getProtocolActionsData() {
        $messageId = 0;
        $result = array();
        if(!empty($this->params[0]))
            $messageId = $this->params[0];
        $items = $this->loadProtocolActionsData();
        if($messageId) {
            foreach ($items as $key => $value) {
                if($value['message_id'] == $messageId) {
                    $result[] = $value;
                }
            }
        } else {
            $result = $items;
        }

        // print_r($result); die;
        return $result;
    }

    public function getCategoryList($params = array()) {
        // print_r($_SERVER); die;
        // except('xa-xa');
        $rootId = 1;
        $result = array();
        $typeFormat = $this->params[0];
        $categoryList = $this->loadCategoryList();
        if($typeFormat != 'form')
            return $categoryList;

        $rootItem = $this->getRootCategory($categoryList, $rootId);
        $category = $this->formCategories($categoryList, $rootId);
        $rootItem[$this->childrenName] = $category;
        $result[] = $rootItem;
        return $result;
    }

    public function saveMessage() {
        $idName   = 'id';
        $postData = $this->postData();
        $filePath = __DIR__ . '/messages.php';
        return $this->saveItem($postData, $filePath, $idName);
    }

    public function deleteMessage() {
        $idName   = 'id';
        $itemId   = $this->params[0];
        $filePath = __DIR__ . '/messages.php';
        return $this->deleteItem($itemId, $filePath, $idName);
    }

    public function savePerformData() {
        $idName   = 'id';
        $postData = $this->postData();
        // $newPerformData = array();
        // print_r($postData); die;
        $filePath = __DIR__ . '/performsData.php';
        $performData = $this->loadPerformsData();
        $len = count($performData);
        foreach ($postData as $x => $value) {
            $item = (array)$value;
            $itemId = $item[$idName];
            if(!$itemId) {
                $item[$idName] = ++$len;
                $performData[] = $item;
                $len = count($performData);
            } else {
                foreach ($performData as $key => $perform) {
                    if($perform[$idName] == $itemId) {
                        $performData[$key] = $item;
                        break;
                    }
                }
            }
            //$newPerformData[] = (array)$value;
        }

        //print_r(array($postData, $performData)); die;
        return $this->save($filePath, $performData);
    }

    //////////////////////////
    //// Protected function

    protected function formCategories($items, $rootId) {
        $idName = $this->idName;
        $parentIdName = $this->parentIdName;
        $childrenName = $this->childrenName;
        $children = array();
        foreach ($items as $key => $item) {
            $itemId = $item[$idName];
            $parentId = $item[$parentIdName];
            if($rootId == $parentId) {
                $item[$childrenName] = array();
                $subChildren = $this->formCategories($items, $itemId);
                if(!empty($subChildren)) {
                    $item[$childrenName] = $subChildren;
                }
                $children[] = $item;
            }
        }

        if(empty($children))
            return false;
        return $children;
    }

    protected function getRootCategory($items, $itemId) {
        $idName = 'id';
        foreach ($items as $key => $item) {
            if($itemId == $item[$idName])
                return $item;
        }
        return false;
    }

    protected function saveItem($item, $filePath, $idName = 'id') {

        if(empty($item)) return false;

        $items = include_once $filePath;

        // print_r(array($filePath, $items,$item)); die;

        if(!empty($item[$idName])) {
            $itemId = $item[$idName];
            foreach ($items as $key => $value) {
                if($value[$idName] == $itemId) {
                    $items[$key] = $item;
                }
            }
        } else {
            $newItemId = $this->createItemId($items);
            $item[$idName] = $newItemId;
            $items[]   = $item;
        }
        return $this->save($filePath, $items);
    }


    protected function deleteItem($itemId, $filePath, $idName = 'id') {
        $newItems = array();
        $items = include_once $filePath;
        foreach ($items as $key => $value) {
            $currentId = $value[$idName];
            if($currentId != $itemId) {
                $newItems[] = $value;
            }
        }
        return $this->save($filePath, $newItems);
    }

    protected function save($filePath, $data) {
        // if(count($data) < 5 ) return false;
        // print_r($data);die;
        file_put_contents($filePath, '<?php return ');
        file_put_contents($filePath, var_export($data, true), FILE_APPEND);
        file_put_contents($filePath, ';', FILE_APPEND);
        // $items = $this->loadMessages();
        return true;
    }

    protected function createItemId($items, $idName = 'id') {
        $maxId  = 0;
        foreach ($items as $key => $values) {
            $currentId = $values[$idName];
            if($currentId > $maxId) $maxId = $currentId;
        }
        return ++$maxId;
    }

    protected function loadMessages() {
        $result = require_once __DIR__ . '/messages.php';
        return $result;
    }

    protected function loadCategoryList() {
        $result = require_once __DIR__ . '/categoryList.php';
        return $result;
    }

    protected function loadPerformsData() {
        $result = require_once __DIR__ . '/performsData.php';
        return $result;
    }

    protected function loadProtocolActionsData() {
        $result = require_once __DIR__ . '/protocolActions.php';
        return $result;
    }

    public function getServicesList() {

        $serviceName = '';
        if(!empty($this->params[0]))
           $serviceName = $this->params[0];

        $headers = array(
            'id'          => '№ обращения',
            'date'        => 'Дата обращения',
            'target_date' => 'Плановый срок',
            'control'     => 'K',
            'control_date' => 'Дата снятия с контроля',
            'category_name'   => 'Категория обращения',
            'prop'         => 'Причина обращения',
            'district'     => 'Район',
            'addr'         => 'Адрес обращения',
            'declarer'     => 'Заявитель',
            'phone'        => 'Телефон',
            'sender'       => 'Регистратор обращения',
            'operator'     => 'Дежурный',
            'service_org'  => 'Обслуживающая организация',

            'address_id'   => '',
            'districtName' => '',
            'cat_id'       => 'Id категории',
            'resident'     => 'Авакумова Н.П',
            'prop_message' => '',
        );

        $districtList = array(
            array( 'id' => 4, 'name'  => 'Фрунзенский'),
            array( 'id' => 5, 'name'  => 'Невский'),
            array( 'id' => 6, 'name'  => 'Центральный'),
            array( 'id' => 7, 'name'  => 'Адмиралтейский'),
            array( 'id' => 8, 'name'  => 'Красносельский'),
            array( 'id' => 9, 'name'  => 'Выборгский'),
            array( 'id' => 10, 'name' => 'Красногвардейский'),
            array( 'id' => 11, 'name' => 'Московский'),
            array( 'id' => 12, 'name' => 'Петроградский'),
        );

        $residentUsers = array(
            array( 'id' => 1, 'name'  => 'Петров И.В'),
            array( 'id' => 2, 'name'  => 'Никанорова В.С'),
            array( 'id' => 3, 'name'  => 'Пельш В.С'),
            array( 'id' => 4, 'name'  => 'Васильева Н.А.'),
        );

        $addressList = array(
            array( 'id' => 1, 'name'  => 'Невский пр., дом 36'),
            array( 'id' => 2, 'name'  => 'Бухаресткая ул.,дом 6'),
            array( 'id' => 3, 'name'  => 'ул. Салова, дом 24'),
            array( 'id' => 4, 'name'  => 'пр. Ветеранов,дом 78'),
        );

        $protocolActions = array(
            array( 'id' => 1, 'name' => 'Передача данных об исполнении'),
            array( 'id' => 2, 'name' => 'Внесение изменений в работы по обращению'),
            array( 'id' => 3, 'name' => 'Карточка прочтена исполнителем'),
            array( 'id' => 4, 'name' => 'Добавление исполнителя'),
            array( 'id' => 5, 'name' => 'Прием обращения от другого регистратора'),
        );

        $performersOrg = array(
            array( 'id' => 1,  'name' => 'Администрация Красногвардейского р-на'),
            array( 'id' => 2,  'name' => 'Администрация Фрунзенского р-на'),
            array( 'id' => 3,  'name' => 'ГКУЖА Петроградского района'),
            array( 'id' => 4,  'name' => 'Администрация Кировского р-на'),
            array( 'id' => 5,  'name' => 'ГКУЖА Кронштатского района'),
            array( 'id' => 6,  'name' => 'Администрация Московского р-на'),
            array( 'id' => 7,  'name' => 'Администрация Пушкинского р-на'),
            array( 'id' => 8,  'name' => 'ГКУЖА Центрального района'),
            array( 'id' => 9,  'name' => 'ГУП Водоканал'),
            array( 'id' => 10, 'name' => 'Комитет по энергетике'),
            array( 'id' => 11, 'name' => 'Комитет по зравохранению'),

            array( 'id' => 12, 'name' => 'Администрация Невского р-на'),
            array( 'id' => 13, 'name' => 'Жилкомсервис № 2 Невского района'),
            array( 'id' => 14, 'name' => 'ГУЖА Невского района'),

            array( 'id' => 15, 'name' => 'СПб ГКУ "АВС"'),
        );

        $messageModel = array (

            'id'   => 0,
            'date' => '2019-10-12',
            'target_date'   => '',
            'control'       => 'K',
            'control_date'  => '',
            'category_name' => 'Эксплуатация жилищного фонда',
            'prop' => 'Вопросы, касающиеся эксплуатации жилищного фонда',
            'district' => 'Выборгский',

            'addr' => 'КОСТРОМСКОЙ ПРОСПЕКТ, д. 21, кв. 5',
            'resident' => 'КАЗАКОВА АНГЕЛИНА КОНСТАНТИНОВНА',
            'phone'  => '3389168',
            'sender' => 'Отдел ДС Администрации Губернатора',
            'operator' => 'Мазго Ю.В.',
            'service_org' => 'Прогресс, ГУП РЭП',

            'district_id'  => 9,
            'cat_id'       => 5,
        );

        $services = array(
            'headers'        => $headers,
            'district_list'  => $districtList,
            'action_types'   => $protocolActions,
            'protocol_actions'   => $protocolActions,
            'performers_org' => $performersOrg,
            'resident_users' => $residentUsers,
            'addresses'      => $addressList,
            'address_list'   => $addressList,
            'message_model'  => $messageModel,
        );

        if(!empty($services[$serviceName])) {
            return $services[$serviceName];
        }

        return $services;
    }
}