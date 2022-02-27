<?php

namespace Aijkl\AdShare;
use PDO;

class AdShareDataBase
{
    private PDO $database;
    function __construct(string $dsn,string $userName,string $password)
    {
        $this->database = new PDO($dsn,$userName,$password);
        $this->database->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    }

    /**
     * @throws UserNotFoundException
     */
    function signIn(SignInRequest $signInRequest) : TokenEntity
    {
        $sqlBuilder = $this->database->prepare("SELECT * FROM users WHERE users.mail = :mail AND users.password_sha256 = :password_sha256");
        $sqlBuilder->bindValue(":mail",$signInRequest->mail);
        $sqlBuilder->bindValue(":password_sha256",$signInRequest->passwordSha256);
        $sqlBuilder->execute();
        $result = $sqlBuilder->fetch(PDO::FETCH_ASSOC);
        if($result == false)
        {
            throw new UserNotFoundException();
        }

        $user = EntityConverter::ConvertToUserEntity($result);
        return $this->createToken($user->id);
    }

    /**
     * @throws UserExistsException
     */
    function signUp(SignUpRequest $signUpRequest) : TokenEntity
    {
        // todo 排他制御 HEW時間なくてやれない...
        if($this->exitsUser($signUpRequest->mail))
        {
            throw new UserExistsException();
        }

        $id = hash("sha256",strval(rand(PHP_INT_MIN,PHP_INT_MAX)));
        $sqlBuilder = $this->database->prepare("INSERT INTO users (users.id,users.name,users.mail,users.password_sha256) VALUES (:id,:name,:mail,:password_sha256)");
        $sqlBuilder->bindValue(":id",$id);
        $sqlBuilder->bindValue(":name",$signUpRequest->name);
        $sqlBuilder->bindValue(":mail",$signUpRequest->mail);
        $sqlBuilder->bindValue(":password_sha256",$signUpRequest->passwordHash256);
        $sqlBuilder->execute();
        $sqlBuilder->fetch(PDO::FETCH_ASSOC);

        return $this->createToken($id);
    }

    /**
     * @throws UserNotFoundException
     */
    function getUser(string $userId) : UserEntity
    {
        $sqlBuilder = $this->database->prepare("SELECT * FROM users WHERE users.id = :userId");
        $sqlBuilder->bindValue(":userId",$userId);
        $sqlBuilder->execute();
        $result = $sqlBuilder->fetch(PDO::FETCH_ASSOC);
        if($result == false)
        {
            throw new UserNotFoundException();
        }

        return EntityConverter::ConvertToUserEntity($result);
    }

    function exitsUser(string $mail) : bool
    {
        $sqlBuilder = $this->database->prepare("SELECT * FROM users WHERE users.mail = :mail");
        $sqlBuilder->bindValue(":mail",$mail);
        $sqlBuilder->execute();
        return $sqlBuilder->fetch(PDO::FETCH_ASSOC) == true;
    }

    function validateToken(string $token): bool
    {
        $sqlBuilder = $this->database->prepare("SELECT * FROM tokens WHERE tokens.token = :token AND tokens.enable");
        $sqlBuilder->bindValue(":token",$token);
        $sqlBuilder->execute();

        return $sqlBuilder->execute() != null;
    }

    /**
     * @throws TokenNotFoundException
     * @throws UserNotFoundException
     */
    function getUserFromToken(string $token): UserEntity
    {
        $sqlBuilder = $this->database->prepare("SELECT * FROM tokens WHERE tokens.token = :token AND tokens.enable");
        $sqlBuilder->bindValue(":token",$token);
        $sqlBuilder->execute();

        $result = $sqlBuilder->fetch(PDO::FETCH_ASSOC);
        if($result == false)
        {
            throw new TokenNotFoundException();
        }
        return $this->getUser($result["user_id"]);
    }

    function createAdvice(string $text,int $likes,string $author_id,bool $valid)
    {
        $sqlBuilder = $this->database->prepare("INSERT INTO advices (text, likes, author_id,valid) VALUES (:text,:likes,:author_id,:valid)");
        $sqlBuilder->bindValue(":text",$text);
        $sqlBuilder->bindValue(":likes",$likes);
        $sqlBuilder->bindValue(":author_id",$author_id);
        $sqlBuilder->bindValue(":valid",$valid);
        $sqlBuilder->execute();
    }

    /**
     * @throws AdviceNotFoundException
     */
    function getAdvice(string $id): AdviceEntity
    {
        $sqlBuilder = $this->database->prepare("SELECT * FROM advices WHERE id = :id");
        $sqlBuilder->bindValue(":id",$id);
        $sqlBuilder->execute();
        $result = $sqlBuilder->fetch(PDO::FETCH_ASSOC);
        if($result === false)
        {
            throw new AdviceNotFoundException();
        }
        return new AdviceEntity($result['id'],$result['text'],$result['likes'],$result['valid']);
    }

    private function createToken(string $userId): TokenEntity
    {
        $token = hash("sha256",strval(rand(PHP_INT_MIN,PHP_INT_MAX)));
        $sqlBuilder = $this->database->prepare("INSERT INTO tokens (tokens.user_id,tokens.token,tokens.enable) VALUES(:user_id,:token,true)");
        $sqlBuilder->bindValue(":user_id",$userId);
        $sqlBuilder->bindValue(":token",$token);
        $sqlBuilder->execute();

        $tokenEntity = new TokenEntity();
        $tokenEntity->token = $token;
        $tokenEntity->userId = $userId;
        return $tokenEntity;
    }
}