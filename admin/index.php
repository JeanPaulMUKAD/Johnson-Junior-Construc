<?php
    if (isset ($_GET["search"]) and ($_GET["search"] == "connexion/login.php"))
     {
        include("connexion/login.php");
     }
     elseif(isset ($_GET["search"]) and ($_GET["search"] == "connexion/reset.php"))
     {
        include("connexion/reset.php");
     }
     elseif(isset ($_GET["search"]) and ($_GET["search"] == "connexion/logout.php"))
    {
        include("connexion/logout.php");
    }
     else
     {
         include("connexion/login.php");
     }
 ?>