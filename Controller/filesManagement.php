<?php 

//######################################### EXERCICES FILES MANAGEMENT#######################################################
function uploadImgExercise(){
    //Recupérer et stocker l'image MCD
            
    // Constantes
    define('TARGET', 'ImgExo/');    // Repertoire cible
    define('MAX_SIZE', 100000);    // Taille max en octets du fichier
    define('WIDTH_MAX', 1000);    // Largeur max de l'image en pixels
    define('HEIGHT_MAX', 1000);    // Hauteur max de l'image en pixels
    
    // Tableaux de donnees
    $tabExt = array('jpg','gif','png','jpeg');    // Extensions autorisees
    $infosImg = array();
    
    // Variables
    $extension = '';
    $message = '';
    $nomImage = 'Img'.$_POST["exerciseName"];

    /************************************************************
     * Script d'upload
     *************************************************************/
    // Recuperation de l'extension du fichier
    $extension  = pathinfo($_FILES['ImgModelMCD']['name'], PATHINFO_EXTENSION);
                
    // On verifie l'extension du fichier
    if(in_array(strtolower($extension),$tabExt))
    {
        // On recupere les dimensions du fichier
        $infosImg = getimagesize($_FILES['ImgModelMCD']['tmp_name']);

        // On verifie le type de l'image
        if($infosImg[2] >= 1 && $infosImg[2] <= 14)
        {
            // On verifie les dimensions et taille de l'image
            if(($infosImg[0] <= WIDTH_MAX) && ($infosImg[1] <= HEIGHT_MAX) && (filesize($_FILES['ImgModelMCD']['tmp_name']) <= MAX_SIZE))
            {
                // Parcours du tableau d'erreurs
                if(isset($_FILES['ImgModelMCD']['error']) 
                    && UPLOAD_ERR_OK === $_FILES['ImgModelMCD']['error'])
                {
                    // On renomme le fichier avec la bonne extension
                    $nomImage = $nomImage .'.'. $extension;

                    // Si c'est OK, on teste l'upload
                    if(move_uploaded_file($_FILES['ImgModelMCD']['tmp_name'], TARGET.$nomImage))
                    {
                        $message = 'Upload réussi !';
                        return [1,$nomImage];
                    }
                    else
                    {
                        // Sinon on affiche une erreur systeme
                        $message = 'Problème lors de l\'upload !';
                        return [0,$message];
                    }
                }
                else
                {
                    $message = 'Une erreur interne a empêché l\'upload de l\'image';
                    return [0,$message];
                }
            }
            else
            {
                // Sinon erreur sur les dimensions et taille de l'image
                $message = 'Erreur dans les dimensions de l\'image !';
                return [0,$message];
            }
        }
        else
        {
            // Sinon erreur sur le type de l'image
            $message = 'Le fichier à uploader n\'est pas une image !';
            return [0,$message];
        }
    }
    else
    {
        // Sinon on affiche une erreur pour l'extension
        $message = 'L\'extension du fichier est incorrecte !';
        return [0,$message];
    }

    
}


function uploadSqlFile(){

    $target="DatabaseExercice"; 
    $wantedExt="sql";
    //check if exercice already exist
    $exerciceNames=BDD::get()->query("SELECT `quiz_name`FROM `quiz`")->fetchAll();
    $thisquizName=$_POST['exerciseName'];
    
    $ext=explode('.', $_FILES["SQLFile"]["name"])[1];
  
    if($ext==$wantedExt){
        
        $verifyDouble=0;
        
        foreach ($exerciceNames as $exoname){
            if ($exoname["quiz_name"]==$thisquizName){
                $verifyDouble=1;
            }
        }
        
        if($verifyDouble==0){
            $nomFile="\\".$_FILES["SQLFile"]["name"];
            move_uploaded_file($_FILES['SQLFile']['tmp_name'], $target.$nomFile);
            $filename=explode('.', $_FILES["SQLFile"]["name"])[0];
            return [1,$filename];
        }
    }else{
        $message="L'extension n'est pas valide ( csv requis)";
        return [0,$message];
    }

}

function uploadCsvQuiz(){

    $target="Csvfiles"; 
    $wantedExt="csv";
    //check if exercice already exist
    $ext=explode('.', $_FILES["QuestionFile"]["name"])[1];

    if($ext==$wantedExt){

        $nomFile="\\".$_FILES["QuestionFile"]["name"];
        move_uploaded_file($_FILES['QuestionFile']['tmp_name'], $target.$nomFile);
        $filename=explode('.', $_FILES["QuestionFile"]["name"])[0];
        return [1,$filename];

    }else
    {
        $message="L'extension n'est pas valide ( csv requis)";
        return [0,$message];
    }
}

function insertQuestionCsvQuiz($Csvfilename,$quizName){// insert all question in db from a csv file for a new quizz
    $row = 1;
    $quizName=BDD::get()->query("SELECT `quiz_id` FROM `quiz` WHERE `quiz_name`= '$quizName' ")->fetchAll(); //get in wich quiz questions will be insert

    if (($handle = fopen("Csvfiles/".$Csvfilename.".csv", "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {//handel=vrai ou faux//1000 caracteres max, "," séparateur
            $num = count($data);
            $row++;
            for ($c=0; $c < $num; $c++) {
                //get data
                $datas=explode(';',$data[$c]);
    
                $intitule=$datas[0];
                $descrp=$datas[1];
                $ans=$datas[2];
                if(isset($datas[3])){
                    $score=$datas[3];
                }else{
                    $score=1;
                }
                $newQuestion = BDD::get()->prepare('INSERT INTO question VALUES (NULL,:question_libele, :question_text, :quiz_answer,:quiz_id,:question_points)');  
    
                //$newQuiz->bindParam(':quiz_id',4);
                $newQuestion->bindParam(':question_libele',$intitule);
                $newQuestion->bindParam(':question_text',$descrp);
                $newQuestion->bindParam(':quiz_answer', $ans);
                $newQuestion->bindParam(':quiz_id',$quizName[0]['quiz_id']);
                $newQuestion->bindParam(':question_points',$score);
            
                $newQuestion->execute();
                
            }
        }
        fclose($handle);
    }
}

function addExercise($questionfile,$sqlfile,$imgfile){
    //create a new quiz : 
    $quiz_name=$_POST["exerciseName"];
    $quiz_difficulty="facile";
    $quiz_description=$_POST["context"];
    $user_id=$_SESSION["user"];
    $quiz_database=$sqlfile;
    $Img_name=$imgfile;

    $newQuiz = BDD::get()->prepare('INSERT INTO quiz VALUES (NULL,:quiz_name, :quiz_difficulty, :quiz_description,:user_id,:quiz_database,:quiz_img)');  

    //$newQuiz->bindParam(':quiz_id',4);
    $newQuiz->bindParam(':quiz_name',$quiz_name);
    $newQuiz->bindParam(':quiz_difficulty',$quiz_difficulty);
    $newQuiz->bindParam(':quiz_description',$quiz_description);
    $newQuiz->bindParam(':user_id',$user_id);
    $newQuiz->bindParam(':quiz_database',$quiz_database);
    $newQuiz->bindParam(':quiz_img',$Img_name);
  
    $newQuiz->execute();

    // insert question in new quiz
    insertQuestionCsvQuiz($questionfile,$quiz_name);

}
//######################################### TEAMS/GROUPS FILES MANAGEMENT#######################################################
function uploadCsvStudents(){

    $target="Csvfiles"; 
    $wantedExt="csv";
    //check if exercice already exist
    $ext=explode('.', $_FILES["fileToUpload"]["name"])[1];

    if($ext==$wantedExt){

        $nomFile="\\".$_FILES["fileToUpload"]["name"];
        move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $target.$nomFile);
        $filename=explode('.', $_FILES["fileToUpload"]["name"])[0];
        return [1,$filename];

    }else
    {
        $message="L'extension n'est pas valide ( csv requis)";
        return [0,$message];
    }
}

function dispatchStudent($filename){
    $row = 1;
    $quizName=BDD::get()->query("SELECT `quiz_id` FROM `quiz` WHERE `quiz_name`= '$quizName' ")->fetchAll(); //get in wich quiz questions will be insert
    if (($handle = fopen("Csvfiles/".$Csvfilename.".csv", "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {//handel=vrai ou faux//1000 caracteres max, "," séparateur
            $num = count($data);
            $row++;
            for ($c=0; $c < $num; $c++) {
                //get data
                $datas=explode(';',$data[$c]);
                
                $team=$datas[0];
                $group=$datas[1];
                $lastname=$datas[2];
                $firstname=$datas[2];
                manageCsvStudent($team,$group,$lastname,$firstname);
                
            }
        }
        fclose($handle);
    }
}

function manageCsvStudent($team,$group,$lastname,$firstname){
    //check if team already exist
    $teamNames=BDD::get()->query("SELECT `equipe_id`,`equipe_name` FROM `equipe`")->fetchAll();
    $teamExist=0;
    foreach($teamNames as $nameteam){
        if($nameteam['equipe_name']==$team){
            $teamExist=1;
            break;
        }
    }
    if($teamExist==0){
        //createTeam
    }
    //check if the group in this team already exist
    //check if student exist
    //check if student is already in this group




}

?>