<?php
/*
Tout le code doit se faire dans ce fichier PHP

Réalisez un formulaire HTML contenant :
- firstname
- lastname
- email
- pwd
- pwdConfirm

Créer une table "user" dans la base de données, regardez le .env à la racine et faites un build de docker
si vous n'arrivez pas à les récupérer pour qu'il les prenne en compte

Lors de la validation du formulaire vous devez :
- Nettoyer les valeurs, exemple trim sur l'email et lowercase (5 points)
- Attention au mot de passe (3 points)
- Attention à l'unicité de l'email (4 points)
- Vérifier les champs sachant que le prénom et le nom sont facultatifs
- Insérer en BDD avec PDO et des requêtes préparées si tout est OK (4 points)
- Sinon afficher les erreurs et remettre les valeurs pertinantes dans les inputs (4 points)

Le design je m'en fiche mais pas la sécurité

Bonus de 3 points si vous arrivez à envoyer un mail via un compte SMTP de votre choix
pour valider l'adresse email en bdd

Pour le : 22 Octobre 2025 - 8h
M'envoyer un lien par mail de votre repo sur y.skrzypczyk@gmail.com
Objet du mail : TP1 - 2IW3 - Nom Prénom
Si vous ne savez pas mettre votre code sur un repo envoyez moi une archive
*/


// connexion bd
$host = 'db';
$port = '5432';
$dbname = 'devdb';
$user = 'devuser';
$password = 'devpass';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$errors = [];
$firstname = '';
$lastname = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pwd = $_POST['pwd'] ?? '';
    $pwdConfirm = $_POST['pwdConfirm'] ?? '';
    
    
    //email
    if (empty($email)) {
        $errors[] = "L'email est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide.";
    }
    
    
    //mot de passe
    if (empty($pwd)) {
        $errors[] = "Le mot de passe est obligatoire.";
    } elseif (strlen($pwd) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif (!preg_match('/[a-z]/', $pwd) || !preg_match('/[A-Z]/', $pwd)) {
        $errors[] = "Le mot de passe doit contenir au moins une majuscule et une minuscule";
    } elseif (!preg_match('/[0-9]/', $pwd)) {
        $errors[] = "Le mot de passe doit contenir au moins un chiffre.";
    } elseif (!preg_match('/[!@#$%&?*_-]/   ', $pwd)) {
        $errors[] = "Le mot de passe doit contenir au moins un de ces charactères spéciaux    ! @ # $ % & ? * _ -";
    }


    //mot de passe confirmer
    if ($pwd !== $pwdConfirm) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }
   
    
    //unicite email
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM \"user\" WHERE email = :email");
            $stmt->execute(['email' => $email]);
            
            if ($stmt->fetch()) {
                $errors[] = "Cet email est déjà utilisé.";
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la vérification de l'email.";
        }
    }
    

    //requete
    if (empty($errors)) {
        try {
            $hashedPwd = password_hash($pwd, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare(
                "INSERT INTO \"user\" (firstname, lastname, email, pwd) 
                 VALUES (:firstname, :lastname, :email, :pwd)"
            );
            
            $stmt->execute([
                'firstname' => $firstname ?: null,
                'lastname' => $lastname ?: null,
                'email' => $email,
                'pwd' => $hashedPwd
            ]);
            
            $success = "Inscription réussie !";
            $firstname = '';
            $lastname = '';
            $email = '';
            
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de l'insertion : " . $e->getMessage();
        }
    }
}
?>




<!DOCTYPE html>
<html lang="en">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<head>
    <title>Formulaire d'inscription</title>
</head>
<body>
    <header>
        <h1>Formulaire d'inscription</h1>
    </header>
    <?php if (!empty($errors)): ?>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo ($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <p><?php echo ($success) ?></p>
    <?php endif; ?>
    


    <form method="POST" action="">
        <label for="firstname">Prénom (facultatif)</label>
        <input type="text" id="firstname" name="firstname" 
               value="<?php echo htmlspecialchars($firstname) ?>">
        <br><br>
        
        <label for="lastname">Nom (facultatif)</label>
        <input type="text" id="lastname" name="lastname" 
               value="<?php echo htmlspecialchars($lastname) ?>">
        <br><br>
        
        <label for="email">Email *</label>
        <input type="email" id="email" name="email" 
               value="<?php echo htmlspecialchars($email) ?>" required>
        <br><br>
        
        <label for="pwd">Mot de passe *</label>
        <input type="password" id="pwd" name="pwd" required>
        <br><br>
        
        <label for="pwdConfirm">Confirmation du mot de passe *</label>
        <input type="password" id="pwdConfirm" name="pwdConfirm" required>
        <br><br>
        
        <button type="submit">S'inscrire</button>
    </form>
</body>
</html>


<style>
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    display: flex;
    justify-content: flex-start;
    align-items: center;
    flex-direction: column;
    padding-top: 40px;
}

.container {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 350px; 
}

h1 {
    margin-bottom: 20px;
    color: #333;
    text-align: center;
}

form {
    background-color: white;
    padding: 30px 40px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    width: 350px;
    max-width: 90vw;
    margin: 0 auto;
    box-sizing: border-box;
}


label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    text-align: left;
}

input {
    width: 100%;
    padding: 8px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

button {
    width: 100%;
    background-color: #3498db;
    color: white;
    padding: 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

button:hover {
    background-color: #2980b9;
}

ul {
    color: red;
    list-style: none;
    padding-left: 0;
    width: 100%;
    text-align: center;
}
</style>