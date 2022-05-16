<?php

namespace Gateway;

use PDO;

class User
{
    /**
     * @var PDO
     */
    private static $instance; // Нельзя делать public по соображениям безопасности

    /**
     * Реализация singleton
     * @return PDO
     */
    public static function getInstance(): PDO
    {
        if (is_null(self::$instance)) {
            // Параметры подключения к базе лучше хранить в отдельном файле, а не хард-кодить в теле класса
            $dsn = 'mysql:dbname=db;host=127.0.0.1';
            $user = 'dbuser';
            $password = 'dbpass';
            self::$instance = new PDO($dsn, $user, $password);
        }

        return self::$instance;
    }

    /**
     * Возвращает список пользователей старше заданного возраста.
     * @param int $ageFrom
     * @return array
     */    
    // Более подходящее название для метода - getUsersByName, исходя из его задачи
    // $limit нужно передавать в качестве необязательного аргумента, это значение не должно зависеть от константы другого класса    
    public static function getUsers(int $ageFrom, int $limit = 10): array
    {   
        $age = $ageFrom;
        if(!is_int($age)) {
            try {
                $age = (int) $age;
            } catch (TypeError $e) {
                return [];
            }
        }
        // $stmt = self::getInstance()->prepare("SELECT id, name, lastName, from, age, settings FROM Users WHERE age > {$ageFrom} LIMIT " . \Manager\User::limit);
        $stmt = self::getInstance()->prepare("SELECT id, name, lastName, `from`, age, settings FROM Users WHERE age > :age LIMIT " . $limit);
        $stmt.bindValue(':age', $age, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $users = [];
        foreach ($rows as $row) {
            $settings = json_decode($row['settings']);
            $users[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'lastName' => $row['lastName'],
                'from' => $row['from'],
                'age' => $row['age'],
                'key' => $settings['key'],
            ];
        }

        return $users;
    }

    /**
     * Возвращает пользователя по имени.
     * @param string $name
     * @return array
     */
    // Более подходящее имя для метода - getUserByName
    public static function user(string $name): array
    {
        //$stmt = self::getInstance()->prepare("SELECT id, name, lastName, from, age, settings FROM Users WHERE name = {$name}");
        $stmt = self::getInstance()->prepare("SELECT id, name, lastName, `from`, age, settings FROM Users WHERE name = :$name LIMIT 1");
        $stmt.bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        $user_by_name = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'id' => $user_by_name['id'],
            'name' => $user_by_name['name'],
            'lastName' => $user_by_name['lastName'],
            'from' => $user_by_name['from'],
            'age' => $user_by_name['age'],
        ];
    }

    /**
     * Добавляет пользователя в базу данных.
     * @param string $name
     * @param string $lastName
     * @param int $age
     * @return int
     */
    public static function add(string $name, string $lastName, int $age): int // должно быть число
    {
        // $sth = self::getInstance()->prepare("INSERT INTO Users (name, lastName, age) VALUES (:name, :age, :lastName)");
        // был нарушен порядок перечисления полей (параметров)
        $sth = self::getInstance()->prepare("INSERT INTO Users (name, age, lastName) VALUES (:name, :age, :lastName)");
        $sth->execute([':name' => $name, ':age' => $age, ':lastName' => $lastName]);

        return self::getInstance()->lastInsertId();
    }
}