<?php

/**
 *  AGORA
 * 	©  Logma, 2019
 * @package default
 * @author MD
 * @version    1.0
 * @link       http://www.php.net/manual/fr/book.pdo.php
 * 
 * Classe d'accès aux données. 
 * Utilise les services de la classe PDO
 * pour l'application AGORA
 * Les attributs sont tous statiques,
 * $monPdo de type PDO 
 * $monPdoJeux qui contiendra l'unique instance de la classe
 */
class PdoJeux {

    private static $monPdo;
    private static $monPdoJeux = null;

    /**
     * Constructeur privé, crée l'instance de PDO qui sera sollicitée
     * pour toutes les méthodes de la classe
     */
    private function __construct() {
		// A) >>>>>>>>>>>>>>>   Connexion au serveur et à la base  DSN
		try {   
			// encodage
			$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'');
            PdoJeux::$monPdo = new PDO($_ENV['AGORA_DSN'],$_ENV['AGORA_DB_USER'],$_ENV['AGORA_DB_PWD'], $options);
			// Crée une instance (un objet) PDO qui représente une connexion à la base
            PdoJeux::$monPdo = new PDO($_ENV['AGORA_DSN'],$_ENV['AGORA_DB_USER'],$_ENV['AGORA_DB_PWD'], $options);
			// configure l'attribut ATTR_ERRMODE pour définir le mode de rapport d'erreurs 
			// PDO::ERRMODE_EXCEPTION: émet une exception 
			PdoJeux::$monPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			// configure l'attribut ATTR_DEFAULT_FETCH_MODE pour définir le mode de récupération par défaut 
			// PDO::FETCH_OBJ: retourne un objet anonyme avec les noms de propriétés 
			//     qui correspondent aux noms des colonnes retournés dans le jeu de résultats
			PdoJeux::$monPdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
		}
		catch (PDOException $e)	{	// $e est un objet de la classe PDOException, il expose la description du problème
			die('<section id="main-content"><section class="wrapper"><div class = "erreur">Erreur de connexion à la base de données !<p>'
				.$e->getmessage().'</p></div></section></section>');
		}
    }
	
    /**
     * Destructeur, supprime l'instance de PDO  
     */
    public function _destruct() {
        PdoJeux::$monPdo = null;
    }

    /**
     * Fonction statique qui crée l'unique instance de la classe
     * Appel : $instancePdoJeux = PdoJeux::getPdoJeux();
     * 
     * @return l'unique objet de la classe PdoJeux
     */
    public static function getPdoJeux() {
        if (PdoJeux::$monPdoJeux == null) {
            PdoJeux::$monPdoJeux = new PdoJeux();
        }
        return PdoJeux::$monPdoJeux;
    }

	//==============================================================================
	//
	//	METHODES POUR LA GESTION DES GENRES
	//
	//==============================================================================
	
    /**
     * Retourne tous les genres sous forme d'un tableau d'objets 
     * 
     * @return le tableau d'objets  (Genre)
     */
    public function getLesGenres() {
  		$requete =  'SELECT idGenre as identifiant, libGenre as libelle 
						FROM genre 
						ORDER BY libGenre';
		try	{	 
			$resultat = PdoJeux::$monPdo->query($requete);
			$tbGenres  = $resultat->fetchAll();	
			return $tbGenres;		
		}
		catch (PDOException $e)	{  
			die('<div class = "erreur">Erreur dans la requête !<p>'
				.$e->getmessage().'</p></div>');
		}
    }

	
	/**
	 * Ajoute un nouveau genre avec le libellé donné en paramètre
	 * 
	 * @param $libGenre : le libelle du genre à ajouter
	 * @return l'identifiant du genre crée
	 */
    public function ajouterGenre($libGenre) {
        try {
            $requete_prepare = PdoJeux::$monPdo->prepare("INSERT INTO genre "
                    . "(idGenre, libGenre) "
                    . "VALUES (0, :unLibGenre) ");
            $requete_prepare->bindParam(':unLibGenre', $libGenre, PDO::PARAM_STR);
            $requete_prepare->execute();
			// récupérer l'identifiant crée
			return PdoJeux::$monPdo->lastInsertId(); 
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
	
	
	 /**
     * Modifie le libellé du genre donné en paramètre
     * 
     * @param $idGenre : l'identifiant du genre à modifier  
     * @param $libGenre : le libellé modifié
     */
    public function modifierGenre($idGenre, $libGenre) {
        try {
            $requete_prepare = PdoJeux::$monPdo->prepare("UPDATE genre "
                    . "SET libGenre = :unLibGenre "
                    . "WHERE genre.idGenre = :unIdGenre");
            $requete_prepare->bindParam(':unIdGenre', $idGenre, PDO::PARAM_INT);
            $requete_prepare->bindParam(':unLibGenre', $libGenre, PDO::PARAM_STR);
            $requete_prepare->execute();
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
	
	
	/**
     * Supprime le genre donné en paramètre
     * 
     * @param $idGenre :l'identifiant du genre à supprimer 
     */
    public function supprimerGenre($idGenre) {
       try {
            $requete_prepare = PdoJeux::$monPdo->prepare("DELETE FROM genre "
                    . "WHERE genre.idGenre = :unIdGenre");
            $requete_prepare->bindParam(':unIdGenre', $idGenre, PDO::PARAM_INT);
            $requete_prepare->execute();
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }


    /**
     * Retourne tous les genres sous forme d'un tableau d'objets
     *    avec également le nombre de jeux de ce genre
     *
     * @return le tableau d'objets  (Genre)
     */
    public function getLesGenresComplet() {
        $requete =  'SELECT G.idGenre as identifiant, G.libGenre as libelle, G.idSpecialiste AS idSpecialiste, CONCAT(P.prenomPersonne, " ", P.nomPersonne)  AS nomSpecialiste, 
         (SELECT COUNT(refJeu) FROM jeu_video AS J WHERE J.idGenre = G.idGenre) AS nbJeux 
      FROM genre AS G
      LEFT OUTER JOIN personne  AS P ON G.idSpecialiste = P.idPersonne
      ORDER BY G.libGenre';
        try    {
            $resultat = PdoJeux::$monPdo->query($requete);
            $tbGenres  = $resultat->fetchAll();
            return $tbGenres;
        }
        catch (PDOException $e)    {
            die('<div class = "erreur">Erreur dans la requête !<p>'
                .$e->getmessage().'</p></div>');
        }
    }

    /**
     * Retourne l'identifiant et le nom complet de toutes les personnes sous forme d'un tableau d'objets
     *
     * @return le tableau d'objets
     */
    public function getLesPersonnes() {
        $requete =  'SELECT idPersonne as identifiant, CONCAT(prenomPersonne, " ", nomPersonne)  AS libelle 
            FROM personne 
            ORDER BY nomPersonne';
        try    {
            $resultat = PdoJeux::$monPdo->query($requete);
            $tbPersonnes  = $resultat->fetchAll();
            return $tbPersonnes;
        }
        catch (PDOException $e)    {
            die('<div class = "erreur">Erreur dans la requête !<p>'
                .$e->getmessage().'</p></div>');
        }
    }

    // //==============================================================================
	// //
	// //	METHODES POUR LA GESTION DES JEUX
	// //
	// //==============================================================================
	
    /**
    * Retourne tous les jeux sous forme d'un tableau d'objets 
    * 
    * @return le tableau d'objets  (Jeux)
    */
    public function getLesJeux() {
  		$requete =  'SELECT J.refJeu, P.libPlateforme, Pe.ageLimite, G.libGenre, M.nomMarque, J.nom, J.prix, J.dateParution
						FROM jeu_video AS J
						INNER JOIN plateforme AS P ON J.idPlateforme = P.idPlateforme
						INNER JOIN pegi AS Pe ON J.idPegi = Pe.idPegi
						INNER JOIN genre AS G ON J.idGenre = G.idGenre
						INNER JOIN marque AS M ON J.idMarque = M.idMarque';
		try	{	 
			$resultat = PdoJeux::$monPdo->query($requete);
			$tbJeux  = $resultat->fetchAll();	
			return $tbJeux;		
		}
		catch (PDOException $e)	{  
			die('<div class = "erreur">Erreur dans la requête !<p>'
				.$e->getmessage().'</p></div>');
		}
    }

    //ajouter un jeu vidéo
    public function ajouterJeu($libPlateforme, $ageLimite, $libGenre, $nomMarque, $nom, $prix, $dateParution)
	{
        try {
            $requete_prepare = PdoJeux::$monPdo->prepare("INSERT INTO jeu_video"
                    . "(refJeu, libPlateforme, ageLimite, libGenre, nomMarque, nom, prix, dateParution) "
                    . "VALUES (0, :unLibPlateforme, :unAgeLimite, :unLibGenre, :unNom, :unPrix, :uneDateParution) ");
			$requete_prepare->bindParam(':unLibPlateforme', $libPlateforme, PDO::PARAM_INT);
			$requete_prepare->bindParam(':unAgeLimite', $ageLimite, PDO::PARAM_INT);
			$requete_prepare->bindParam(':unLibGenre', $libGenre, PDO::PARAM_INT);
			$requete_prepare->bindParam(':unNomMarque', $nomMarque, PDO::PARAM_INT);
			$requete_prepare->bindParam(':unNom', $nom, PDO::PARAM_STR);
			$requete_prepare->bindParam(':unPrix', $prix, PDO::PARAM_STR);
			$requete_prepare->bindParam(':uneDateParution', $dateParution, PDO::PARAM_INT);
            $requete_prepare->execute();
			// récupérer l'identifiant crée
		    return PdoJeux::$monPdo->lastInsertId(); 
        } 
		catch (Exception $e) {
            die($e->getMessage());
        }
    }
	

    public function modifierJeu($idPlateforme, $idPegi, $idGenre, $idMarque, $nom, $prix, $dateParution){
        try {
            $requete_prepare = PdoJeux::$monPdo->prepare("UPDATE jeu_video"
					. "SET idPlateforme = ':unIdPlateforme', 
					       idPegi = ':unIdPegi', 
						   idGenre = ':unIdGenre', 
						   idMarque = ':unIdMarque', 
						   nom = ':unNom', 
						   prix = ':unPrix', 
						   dateParution = ':uneDateParution'"
                    . "WHERE refJeu = ':uneRefJeu'");
            $requete_prepare->bindParam(':uneRefJeu', $refJeu, PDO::PARAM_INT);
            $requete_prepare->bindParam(':unIdPlateforme', $idPlateforme, PDO::PARAM_INT);
			$requete_prepare->bindParam(':unIdPegi', $idPegi, PDO::PARAM_INT);
			$requete_prepare->bindParam(':unIdGenre', $idGenre, PDO::PARAM_INT);
			$requete_prepare->bindParam(':unIdMarque', $idMarque, PDO::PARAM_INT);
			$requete_prepare->bindParam(':unNom', $nom, PDO::PARAM_INT);
			$requete_prepare->bindParam(':unPrix', $prix, PDO::PARAM_STR);
			$requete_prepare->bindParam(':uneDateParution', $dateParution, PDO::PARAM_INT);
            $requete_prepare->execute();
        } 
		catch (Exception $e) 
		{
             die($e->getMessage());
        }
    }
	
    public function supprimerJeu($refJeu) {
       try 
	   {
            $requete_prepare = PdoJeux::$monPdo->prepare("DELETE FROM jeu_video"
                    . "WHERE jeu_video.refJeu = :uneRefJeu");
            $requete_prepare->bindParam(':uneRefJeu', $refJeu, PDO::PARAM_INT);
            $requete_prepare->execute();
        } catch (Exception $e) 
		{
            die($e->getMessage());
        }
    }	
	
	   // afficherListe($tbPegi , 'lstPegi', 1, '');
	   // afficherListe($tbPlateformes , 'lstPlateforme', 1, '');
	   // afficherListe($tbGenres , 'lstGenre', 1, '');
	   // afficherListe($tbMarques , 'lstMarque', 1, '');
	   
		//==============================================================================
	//
	//	METHODES POUR LA GESTION DES PEGI
	//
	//==============================================================================
	
    public function getLesPegi() {
  		$requete =  'SELECT idPegi as identifiant, ageLimite as libelle, descPegi as description 
						FROM pegi 
						ORDER BY ageLimite';
		try	{	 
			$resultat = PdoJeux::$monPdo->query($requete);
			$tbPegi  = $resultat->fetchAll();	
			return $tbPegi;		
		}
		catch (PDOException $e)	{  
			die('<div class = "erreur">Erreur dans la requête !<p>'
				.$e->getmessage().'</p></div>');
		}
    }

    public function ajouterPegi($ageLimite,$descPegi) {
        try {
            $requete_prepare = PdoJeux::$monPdo->prepare("INSERT INTO pegi "
                    . "(idPegi, ageLimite, descPegi) "
                    . "VALUES (0, :unAgeLimite, :uneDescPegi) ");
            $requete_prepare->bindParam(':unAgeLimite', $ageLimite, PDO::PARAM_INT);
     		$requete_prepare->bindParam(':uneDescPegi', $descPegi, PDO::PARAM_STR);
            $requete_prepare->execute();
			// récupérer l'identifiant crée
			return PdoJeux::$monPdo->lastInsertId(); 
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
	
    public function modifierPegi($idPegi, $ageLimite, $descPegi) {
        try {
            $requete_prepare = PdoJeux::$monPdo->prepare("UPDATE pegi "
                    . "SET ageLimite = :unAgeLimite,
					       descPegi = :uneDescPegi "
                    . "WHERE pegi.idPegi = :unIdPegi");
            $requete_prepare->bindParam(':unIdPegi', $idPegi, PDO::PARAM_INT);
            $requete_prepare->bindParam(':unAgeLimite', $ageLimite, PDO::PARAM_INT);
			$requete_prepare->bindParam(':uneDescPegi', $descPegi, PDO::PARAM_STR);
            $requete_prepare->execute();
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
	
    public function supprimerPegi($idPegi) {
       try {
            $requete_prepare = PdoJeux::$monPdo->prepare("DELETE FROM pegi "
                    . "WHERE pegi.idPegi = :unIdPegi");
            $requete_prepare->bindParam(':unIdPegi', $idPegi, PDO::PARAM_INT);
            $requete_prepare->execute();
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
	
	//==============================================================================
	//
	//	METHODES POUR LA GESTION DES MARQUES
	//
	//==============================================================================
	

    public function getLesMarques() {
  		$requete =  'SELECT idMarque as identifiant, nomMarque as libelle, paysOrigine as paysOriginal
						FROM marque
						ORDER BY nomMarque';
		try	{	 
			$resultat = PdoJeux::$monPdo->query($requete);
			$tbMarques  = $resultat->fetchAll();	
			return $tbMarques;		
		}
		catch (PDOException $e)	{  
			die('<div class = "erreur">Erreur dans la requête !<p>'
				.$e->getmessage().'</p></div>');
		}
    }

    public function ajouterMarque($nomMarque, $paysOrigine) {
        try {
            $requete_prepare = PdoJeux::$monPdo->prepare("INSERT INTO marque "
                    . "(idMarque, nomMarque, paysOrigine) "
                    . "VALUES (0, :unNomMarque, :unPaysOrigine) ");
            $requete_prepare->bindParam(':unNomMarque', $nomMarque, PDO::PARAM_STR);
            $requete_prepare->bindParam(':unPaysOrigine', $paysOrigine, PDO::PARAM_STR);

            $requete_prepare->execute();

			return PdoJeux::$monPdo->lastInsertId(); 
        } 
		catch (Exception $e) {
            die($e->getMessage());
        }
    }
	
    public function modifierMarque($idMarque, $nomMarque, $paysOrigine) {
        try {
            $requete_prepare = PdoJeux::$monPdo->prepare("UPDATE marque "
                    . "SET nomMarque = ':unNomMarque',
                           paysOrigine = ':unPaysOrigine'"
                    . "WHERE marque.idMarque = :'unIdMarque'");
            $requete_prepare->bindParam(':unIdMarque', $idMarque, PDO::PARAM_INT);
            $requete_prepare->bindParam(':unNomMarque', $nomMarque, PDO::PARAM_STR);
            $requete_prepare->bindParam(':unPaysOrigine', $paysOrigine, PDO::PARAM_STR);


            $requete_prepare->execute();
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    public function supprimerMarque($idMarque) {
       try {
            $requete_prepare = PdoJeux::$monPdo->prepare("DELETE FROM marque "
                    . "WHERE marque.idMarque = :unIdMarque");
            $requete_prepare->bindParam(':unIdMarque', $idMarque, PDO::PARAM_INT);
            $requete_prepare->execute();
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
	
	
	//==============================================================================
	//
	//	METHODES POUR LA GESTION DES PLATEFORMES
	//
	//==============================================================================

    public function getLesPlateformes() {
  		$requete =  'SELECT idPlateforme as identifiant, libPlateforme as libelle
						FROM plateforme
						ORDER BY libPlateforme';
		try	{	 
			$resultat = PdoJeux::$monPdo->query($requete);
			$tbPlateformes  = $resultat->fetchAll();	
			return $tbPlateformes;		
		}
		catch (PDOException $e)	{  
			die('<div class = "erreur">Erreur dans la requête !<p>'
				.$e->getmessage().'</p></div>');
		}
    }

    public function ajouterPlateforme($libPlateforme) {
        try {
            $requete_prepare = PdoJeux::$monPdo->prepare("INSERT INTO plateforme"
                    . "(idPlateforme, libPlateforme) "
                    . "VALUES (0, :unlibPlateforme) ");
            $requete_prepare->bindParam(':unlibPlateforme', $libPlateforme, PDO::PARAM_STR);
     		
            $requete_prepare->execute();
			// récupérer l'identifiant crée
			return PdoJeux::$monPdo->lastInsertId(); 
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    public function modifierPlateforme($idPlateforme, $libPlateforme) {
        try {
            $requete_prepare = PdoJeux::$monPdo->prepare("UPDATE plateforme "
                    . "SET libPlateforme = :unLibPlateforme "
                    . "WHERE plateforme.idPlateforme = :unIdPlateforme");
            $requete_prepare->bindParam(':unIdPlateforme', $idPlateforme, PDO::PARAM_INT);
            $requete_prepare->bindParam(':unLibPlateforme', $libPlateforme, PDO::PARAM_STR);
			
            $requete_prepare->execute();
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
	
    public function supprimerPlateforme($idPlateforme) {
       try {
            $requete_prepare = PdoJeux::$monPdo->prepare("DELETE FROM plateforme "
                    . "WHERE plateforme.idPlateforme = :unIdPlateforme");
            $requete_prepare->bindParam(':unIdPlateforme', $idPlateforme, PDO::PARAM_INT);
            $requete_prepare->execute();
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
    //=============================================================================
	//	METHODES POUR LA GESTION DES MEMBRES
	//
	//==============================================================================
	
	 public function getLesMembres_BK() {
  		$requete =  'SELECT idMembre as identifiant_BK, nomMembre as libelle_BK
						FROM membre
						ORDER BY nomMembre';
		try	{	 
			$resultat = PdoJeux::$monPdo->query($requete);
			$tbPlateformes  = $resultat->fetchAll();	
			return $tbPlateformes;		
		}
		catch (PDOException $e)	{  
			die('<div class = "erreur">Erreur dans la requête !<p>'
				.$e->getmessage().'</p></div>');
		}
    }

    //==============================================================================
    //
    // METHODES POUR LA GESTION DES PERSONNES
    //
    //==============================================================================
    /**
        * Retourne l'identifiant, le nom et le prénom de la personne correspondant
        *au login et mdp
        *
        *@return l'objet ou null si cette personne n'existe pas avec ce mot de passe
        */
        public function getUnePersonne($LoginPersonne, $mdpPersonne) {
            try {
                // préparer la requête
                $requete_prepare = PdoJeux::$monPdo->prepare('SELECT idPersonne, prenomPersonne,
                                                              nomPersonne, mdpPersonne, selPersonne 
                                                             FROM personne WHERE loginPersonne = :unLoginPersonne');
                // associer les valeurs aux paramètres
                $requete_prepare->bindParam(':unLoginPersonne', $LoginPersonne, PDO::PARAM_STR);
                // exécuter la requête
                $requete_prepare->execute();
                // récupérer l'objet
                if ($utilisateur = $requete_prepare->fetch()) {
                    // vérifier le mot de passe
                    // le mot de passe transmis par le formulaire est le hash du mot de passe saisi
                    // le mot de passe enregistré dans la base doit correspondre au hash du (hash transmis concaténé au sel)
                    // hash ('sha512',$chaine) : fonction de hachage PHP
                    if ($utilisateur->mdpPersonne == hash('sha512', $mdpPersonne . $utilisateur->selPersonne)) {
                        return $utilisateur;
                    }
                }

                return null;

            }    // fin try
            catch (PDOException $e)   {
                die('<div class = "erreur">Erreur dans la requête !<p>'
                .$e->getmessage().'</p></div>');
            }
    }



}
?>