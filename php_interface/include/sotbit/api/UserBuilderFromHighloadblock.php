<?php

namespace Sotbit\Custom\Api;

use CGroup;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Loader;
use CUser;
use Bitrix\Main\Mail\Event;

class UserBuilderFromHighloadblock
{
    public static function startUserBuilding()
    {
        $user_builder = new self();
        //получение содержимого highload блока
        $block_data = $user_builder->getHighloadblockData('Kontragenty');

        //создание пользователей согласно highload блока
        $user_builder->userBuilder($block_data);

        //очистка содержимого
        $user_builder->cleanHighloadblockData('Kontragenty');

        return "Sotbit\Custom\Api\UserBuilderFromHighloadblock::startUserBuilding();";
    }

    public static function getHighloadblockListToFile()
    {
        $user_builder = new self();

        $block_data = $user_builder->getHighloadblockData('Kontragenty');
        while($data = $block_data->Fetch())
        {
            //debug($data);
            file_put_contents(__DIR__.'/'.__LINE__.'.txt', print_r($data, true), FILE_APPEND);
        }
    }

    public function getGroupIdByName($name): int
    {
        $rsGroups = CGroup::GetList ($by = "c_sort", $order = "asc", Array ("NAME" => $name));
        $arGroup = $rsGroups->Fetch();
        return $arGroup["REFERENCE_ID"];
    }

    public function getHighloadblockData(string $block_name): \Bitrix\Main\ORM\Query\Result
    {
        //Получаем содержимое Highbloadlock
        Loader::includeModule("highloadblock");

        $hlBlock = HL\HighloadBlockTable::getList([
            'filter' => ['=NAME' => $block_name],
            'select' => ["*"],
            'cache' => [
                "ttl" => 360000
            ]
        ])->fetch();

        $entity = HL\HighloadBlockTable::compileEntity($hlBlock);
        $entity_data_class = $entity->getDataClass();

        $rsData = $entity_data_class::getList(array(
            "select" => array("*"),
            "order" => array("ID" => "ASC"),
            "filter" => array("") // Задаем параметры фильтра выборки
        ));

        return $rsData;
    }

    public function cleanHighloadblockData(string $block_name): bool
    {
        //Получаем содержимое Highloadblock
        Loader::includeModule("highloadblock");

        $hlBlock = HL\HighloadBlockTable::getList([
            'filter' => ['=NAME' => $block_name],
            'select' => ["*"],
            'cache' => [
                "ttl" => 360000
            ]
        ])->fetch();

        $entity = HL\HighloadBlockTable::compileEntity($hlBlock);
        $entity_data_class = $entity->getDataClass();

        $rsData = $entity_data_class::getList(array(
            "select" => array("*"),
            "order" => array("ID" => "ASC"),
            "filter" => array("") // Задаем параметры фильтра выборки
        ));

        //Удаляем содержимое Highloadblock
        while($arData = $rsData->Fetch())
        {
            //file_put_contents(__DIR__.'/'.__LINE__.'.txt', print_r($arData['ID']."\n", true), FILE_APPEND);
            if($arData['ID'] == 6615){ // тестовое удаление конкретного поля из highload
                $result = $entity_data_class::delete($arData['ID']);
                if (!$result->isSuccess()) {
                    $errors = $result->getErrorMessages(); // получаем сообщения об ошибках
                }
                break;
            }
        }

        return true;
    }

    public function userBuilder($rsData)
    {
        // Создание новых пользователей на основе Highblock 'Kontragenty'
        $i=0;

        while($arData = $rsData->Fetch())
        {
            if(!empty($arData["UF_KATEGORIYATSEN"])) {
                $user_group_id = $this->getGroupIdByName($arData["UF_KATEGORIYATSEN"]);
            }
            else
            {
                $user_group_id = $this->getGroupIdByName("Gold");
            }

            $arFields = Array(
                "NAME"              => $arData["UF_NAME"],
                "EMAIL"             => $arData["UF_LOGINDLYASAYTA"],
                "LOGIN"             => "default_user".$i++,
                "LID"               => "ru",
                "ACTIVE"            => "Y",
                "GROUP_ID"          => array(3, 4, $user_group_id),
                "PASSWORD"          => $arData["UF_PAROLDLYASAYTA"],
                "CONFIRM_PASSWORD"  => $arData["UF_PAROLDLYASAYTA"],
            );

            $rsUser = CUser::GetByLogin($arFields["EMAIL"]);

            if ($arUser = $rsUser->Fetch()) // если пользователь уже существует обновить ему группы согласно Highblock 'Kontragenty'
            {

                $arGroups = CUser::GetUserGroup($arUser['ID']);

                if(!in_array($user_group_id, $arGroups)) { // добавить группу если такой нет
                    $arGroups[] = $user_group_id;
                }

                /*if (in_array("13", $arGroups)) { // удалить группу 13 если есть такая
                    $key = array_search("13",$arGroups);
                    if ($key !== false) {
                        unset($arGroups[$key]); // удалить
                        //$arGroups[$key] = 14; // заменить
                    }
                }*/

                CUser::SetUserGroup($arUser['ID'], $arGroups);

            }
            else
            {
                if(!empty($arData["UF_LOGINDLYASAYTA"])) {  // создаём только в случае наличия email в highblock
                    $USER = new CUser;
                    //$USER->Add($arFields);

                    $USER->Register(
                        $arFields["LOGIN"],
                        $arFields["NAME"],
                        "",
                        $arFields["PASSWORD"],
                        $arFields["CONFIRM_PASSWORD"],
                        $arFields["EMAIL"]
                    );
                    CUser::SetUserGroup($USER->GetID(), $arFields["GROUP_ID"]);

//                    Event::send(array(  // отправка сообщения
//                        "EVENT_NAME" => "NEW_USER",
//                        "LID" => "b1",
//                        "C_FIELDS" => array(
//                            "EMAIL" => $arFields["EMAIL"],
//                            "USER_NAME" => $arFields["NAME"],
//                            "USER_EMAIL" => $arFields["EMAIL"],
//                        ),
//                    ));
                }
            }

            if($i == 10) {
                break;
            }

        }

    }

}