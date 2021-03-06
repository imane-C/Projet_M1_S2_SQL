<?php

function dataBaseComparision($dbname,$dbnameCorrec,$request,$quizId,$questionId){
    /**
     * @param String $dbname (name of user database), String $dbnameCorrec (name of the correct database), String $request (input sql request by user), integer $quizId,interger $questionId
     * @return array(): [0] String reponse text, [1] String question score (number of points), [2] String valid (validity of user request 0 no or 1 yes)
     **/
    /////////////////////////////apply requests////////////////////////////

    try{
        $conTest = new PDO("mysql:host=localhost;dbname=".$dbname."","root","");
        $requestTest=$conTest->prepare($_POST["reponse"]);
    } catch(PDOException $e){
        die(" ERREUR: Impossible de se connecter à la bbd test: " . $e->getMessage());
    }

    //if user request doesn't work on their db, wrong answer 
    if(!$requestTest->execute()){
        return array("Requête invalide", 0, 0);
    }else{
        //get correct request and question points from website db
        $conGetRequest = BDD::get()->query("SELECT `question_answer`, `question_points` FROM `question` WHERE`quiz_id`=$quizId AND `question_id`=$questionId")->fetchAll();
        $trueRequest=$conGetRequest[0][0];
        $points=$conGetRequest[0][1];

        //apply correct request on correction db
        $conCorrec = new PDO("mysql:host=localhost;dbname=".$dbnameCorrec."","root","");
        $requestCorrec=$conCorrec->prepare($trueRequest);
        $requestCorrec->execute();
            
        /////////////////////////////Get database data////////////////////////////
        //get tables from test db
        $tableTest=getTables($dbname);

        //get tables from correction db
        $tableCorrec=getTables($dbnameCorrec);

        //check count and compare number of tables
        if(!(count($tableTest)==count($tableCorrec))){

            return array("Pas de même nombre de tables", 0, 0);

        } else{

             //check if tables names are the same
            for($i=0;$i<count($tableTest);$i++){

                if(!($tableTest[$i][0]==$tableCorrec[$i][0])){
                    return array("Erreur nom de table", 0, 0);
                }

                $tableName=$tableTest[$i][0];

                //fetch each table data test
                $fetchTest=$conTest->prepare("SELECT * FROM $tableName");
                $fetchTest->execute();
                $dataTest= $fetchTest->fetchAll();

                //fetch each table data correction
                $fetchCorrec=$conCorrec->prepare("SELECT * FROM $tableName");
                $fetchCorrec->execute();
                $dataCorrec= $fetchCorrec->fetchAll();

                //Compare data fetched
                foreach ($dataTest as $key => $fields) {
                    foreach ($fields as $field => $value) {
                        if (isset($dataCorrec[$key][$field])) {
                            if ($dataCorrec[$key][$field] != $value) {
                                unset($conTest);
                                unset($conGetRequest);
                                return array("Pas mêmes valeurs", 0, 0);
                                
                            } else {
                                unset($conTest);
                                unset($conGetRequest);
                                return array("Requête valide", 0, 1);
                            }
                        } else{
                            unset($conTest);
                            unset($conGetRequest);
                            return array("Pas mêmes valeurs", 0, 0);
                        }
                    }
                }

            }

        }
    
    }

}
function compareRequeteCorrection($dbname,$dbnameCorrec,$requete,$question_id,$quiz_id){// if we execute this function, the request is supposed to be correct (tes in dataBaseComparision(), ligne 1)
    try{
        $conTestUser = new PDO("mysql:host=localhost;dbname=".$dbname."","root","");
        
    } catch(PDOException $e){
        die(" ERREUR: Impossible de se connecter à la bbd test: " . $e->getMessage());
    }
    $conGetRequest = BDD::get()->query("SELECT `question_answer`, `question_points` FROM `question` WHERE`quiz_id`=$quiz_id AND `question_id`=$question_id")->fetchAll();
    $trueRequest=$conGetRequest[0][0];
    $points=$conGetRequest[0][1];
    try{
        $conTestCorrec = new PDO("mysql:host=localhost;dbname=".$dbnameCorrec."","root","");
       
    } catch(PDOException $e){
        die(" ERREUR: Impossible de se connecter à la bbd test: " . $e->getMessage());
    }
    $requetUser=$_POST["reponse"];
    $requestTestUser=$conTestUser ->query($requetUser);
    $requestTestCorrec=$conTestCorrec->query($trueRequest);
   
    if($requestTestUser==False AND $requestTestCorrec!=False){
        return array("Requête invalide", 0, 0);
    }else{
        $fieldsUser = array_keys($requestTestUser->fetch(PDO::FETCH_ASSOC));
        $fieldsCorrec = array_keys($requestTestCorrec->fetch(PDO::FETCH_ASSOC));
        $requestTestUser=$requestTestUser->fetchAll();
        $requestTestCorrec=$requestTestCorrec->fetchAll();

    }
    displayRequete($requestTestUser,$requestTestCorrec,$fieldsUser,$fieldsCorrec);
    //verification du nombre de résulat
    $compUser=0;
    $compCorrec=0;
    foreach( $requestTestUser as $user){
        $compUser=$compUser+1;
    }
    foreach($requestTestCorrec as $correc){
        $compCorrec=$compCorrec+1;
    }
    if($compUser!=$compCorrec){
        unset( $conTestUser);
        unset($conTestCorrec);
        return array("Requête invalide, nombre d'élements reçu incorrects", 0, 0);
    }
    //verification du contenu
    $goodLines=0;
    foreach( $requestTestUser as $user){
        foreach($requestTestCorrec as $correc){
            if($user==$correc){
                $goodLines=1;
                break;
            }
        }
        if($goodLines==0){
            unset( $conTestUser);
            unset($conTestCorrec);
            return array("Requête invalide", 0, 0);
        }else{
            $goodLines=0;
        }
    }
    
    //comparaison des requetes :
    unset( $conTestUser);
    unset($conTestCorrec);
    return array("Requête valide", $conGetRequest[0]['question_points'],1);
}

function writeUserAnswer($query,$userAnswerText,$questionId,$userId,$questionScore, $valid, $quizId){
    /**
     * @param String $userAnswerText (request of user), interger $questionId, integer $userId (name of the correct database), integer $questionScore (number of points of question), integer $valid (validity of request 0 no 1 yes), integer $quizId,
     * @return None (only writing info in database)
     **/

    //////////////////////////////////////////////prepare request to write//////////////////////////////////////////
    $writeAnswer = BDD::get()->prepare('INSERT INTO user_answer VALUES (NULL,:user_answer_query,:user_answer_text, CURRENT_TIMESTAMP, :question_id, :user_id,:question_score,:valide,:quiz_id)'); 

    $writeAnswer->bindParam(':user_answer_query',$query);
    $writeAnswer->bindParam(':user_answer_text',$userAnswerText);
    $writeAnswer->bindParam(':question_id',$questionId);
    $writeAnswer->bindParam(':user_id',$userId);
    $writeAnswer->bindParam(':question_score',$questionScore);
    $writeAnswer->bindParam(':valide',$valid);
    $writeAnswer->bindParam(':quiz_id',$quizId);

    /////////////////////check if asnwer from this user to this question already exists///////////////////////////////

    $checkAnswerIfExists = BDD::get()->query("SELECT `valide` FROM `user_answer` WHERE `user_id` = $userId AND `question_id` = $questionId AND `quiz_id` = $quizId")->fetchAll();
    if(!empty($checkAnswerIfExists[0][0])) //if answer of this question/ linked to quiz exists 
    {
        if((int)$checkAnswerIfExists[0][0] == 1 && $valid == 1){  //if valid 
            $updateTime=BDD::get()->query("UPDATE `user_answer` SET `user_answer_time` = CURRENT_TIMESTAMP WHERE `user_id` = $userId AND `question_id` = $questionId AND `quiz_id` = $quizId");//update timestamp
        }else{//not valid
            $writeAnswer->execute();   //write new user answer
            
        }

    } else{ //if does not exists
        if($valid == 1){//if valid
               $writeAnswer->execute(); //write new answer answer and add score to user
               $writeScore=BDD::get()->query("UPDATE `users` SET `user_score` = `user_score` + $questionScore WHERE `user_id`= $userId");
        }else{//not valid
           $writeAnswer->execute(); //write new answer
        }
    }
}
?>