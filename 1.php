<?php

namespace Manager;

class User
{
    // const limit = 10;
    // Я бы не хранил константу, ответственную за количество записей в виде хард-кода в классе, 
    // а передавал бы $limit в качестве аргумента при вызове необходимого метода

    /**
     * Возвращает пользователей старше заданного возраста.
     * @param int $ageFrom
     * @return array
     */
    public static function getUsers(int $ageFrom): array
    {   
        // Теперь в функцию можно передавать в качестве аргумента как переменную, так и литерал
        $age = $ageFrom;
        if (!is_int($age)) {
            try {
                $age = (int) $age;
            } catch (TypeError $e) {
                return [];
            }            
        }       

        return \Gateway\User::getUsers($age);
    }

    /**
     * Возвращает пользователей по списку имен.
     * @return array
     */
    public static function getByNames($names): array
    {
        // $names лучше передеавать в качестве аргумента функции, а не получать из массива $_GET,
        // т.к. теряется возможность вызова метода без передачи аргументов в url.
        // В дальнейшем появится возможность вызывать метод просто в коде программы
        $users = [];
        // foreach ($_GET['names'] as $name) {
        //     $users[] = \Gateway\User::user($name);
        // }
        
        // Неоптимальный способ получать набор User-ов по одному, каждый раз обращаясь к базе данных за очередным пользователем
        foreach ($names as $name) {
            $users[] = \Gateway\User::user($name);
        }

        return $users;
    }

    /**
     * Добавляет пользователей в базу данных.
     * @param $users
     * @return array
     */    
     // Название метода неподходящее для добавления пользователей
    //public static function users($users): array
    public static function addUsers($users): array
    {   
        if(!is_array($users))
            return [];

        $ids = [];
        try {
            // Сначала открываем транзакцию
            \Gateway\User::getInstance()->beginTransaction();
            // В цикле добавляем всех пользователей
            foreach ($users as $user) {        
                \Gateway\User::add($user['name'], $user['lastName'], $user['age']);                
                $ids[] = \Gateway\User::getInstance()->lastInsertId();
            }
            // И только когда ВСЕ добавлены, закрываем транзакцию
            \Gateway\User::getInstance()->commit();
        } catch (\Exception $e) {
            // В случае исключения откатываем ВСЕ изменения
            \Gateway\User::getInstance()->rollBack();
        }
        // В закомментированной версии кода commit происходит после первой успешной операции add, что закрывает транзакцию и вызовет сбой

        // \Gateway\User::getInstance()->beginTransaction();
        // foreach ($users as $user) {
        //     try {
        //         \Gateway\User::add($user['name'], $user['lastName'], $user['age']);
        //         \Gateway\User::getInstance()->commit();
        //         $ids[] = \Gateway\User::getInstance()->lastInsertId();
        //     } catch (\Exception $e) {
        //         \Gateway\User::getInstance()->rollBack();
        //     }
        // }
        return $ids;
    }
}