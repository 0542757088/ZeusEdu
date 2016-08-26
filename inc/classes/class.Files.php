<?php

class Files
{
    public function __construct()
    {
        setlocale(LC_ALL, 'fr_FR.utf8');
    }

    /**
     * Effacement complet d'un répertoire et des fichiers/répertoires contenus.
     *
     * @param $dir : le répertoire à effacer
     *
     * @return bool : 1 si OK, 0 si pas OK
     */
    public function delTree($dir)
    {
        $files = glob($dir.'*', GLOB_MARK);
        $resultat = true;
        foreach ($files as $file) {
            if (substr($file, -1) == '/') {
                if ($resultat == true) {
                    $resultat = $this->delTree($file);
                }
            } else {
                $resultat = unlink($file);
            }
        }

        if (is_dir($dir) && ($resultat == true)) {
            $resultat = rmdir($dir);
        }

        return ($resultat == true) ? 1 : 0;
    }

     /**
      * recherche de le fileId existant d'un fichier dont on fournit le nom et le path ou insère les données et retourne le nouveau fileId.
      *
      * @param $fileName : le nom du fichier
      * @param $path : le path
      * @param $acronyme : l'abréviation de l'utilisateur actif
      *
      * @return int
      */
     public function findFileId($path, $fileName, $acronyme)
     {
         // recherche d'un éventuel 'fileId' existant pour le fichier
         $details = $this->requestFileDetails($path, $fileName, $acronyme);
         $fileId = isset($details[0]['fileId']) ? $details[0]['fileId'] : null;

         // si on n'a pas trouvé d'enregistrement dans la BD, on ajoute le fichier
         if ($fileId == null) {
             $connexion = Application::connectPDO(SERVEUR, BASE, NOM, MDP);
             $sql = 'INSERT INTO '.PFX.'thotFiles ';
             $sql .= 'SET acronyme=:acronyme, path=:path, fileName=:fileName ';
             $requete = $connexion->prepare($sql);
             $data = array(':acronyme' => $acronyme, ':path' => $path, ':fileName' => $fileName);
             $resultat = $requete->execute($data);
             $fileId = $connexion->lastInsertId();
             Application::DeconnexionPDO($connexion);
         }

         return $fileId;
     }

     /**
      * retourne les détails fileId, shareId, path, filename d'un fichier dont on fournit le nom, le path et l'acronyme du propriétaire
      * renvoie 'null' si pas trouvé.
      *
      * @param $fileName : le nom du fichier
      * @param $path : le path
      * @param $acronyme : l'abréviation de l'utilisateur actif
      *
      * @return int | null
      */
     public function requestFileDetails($path, $fileName, $acronyme)
     {
         $connexion = Application::connectPDO(SERVEUR, BASE, NOM, MDP);
         $sql = 'SELECT files.fileId, share.shareId, acronyme, fileName, path ';
         $sql .= 'FROM '.PFX.'thotFiles AS files ';
         $sql .= 'JOIN '.PFX.'thotShares AS share ON share.fileId = files.fileId ';
         $sql .= 'WHERE acronyme=:acronyme AND path=:path AND fileName=:fileName ';

         $requete = $connexion->prepare($sql);
         $data = array(':acronyme' => $acronyme, ':path' => $path, ':fileName' => $fileName);

         $resultat = $requete->execute($data);
         $liste = array();
         if ($resultat) {
             $requete->setFetchMode(PDO::FETCH_ASSOC);
             while ($ligne = $requete->fetch()) {
                 $liste[] = $ligne;
             }

             return $liste;
         }
     }

    /**
     * retourne la liste des partages d'un fichier dont on fournit le propriétaire, le path et le fileName.
     *
     * @param $path
     * @param $fileName
     * @param $acronyme
     *
     * @return array
     */
    public function getSharesByFileName($path, $fileName, $acronyme)
    {
        $fileId = $this->findFileId($path, $fileName, $acronyme);

        return $this->getSharesByFileId($fileId);
    }

    /**
     * retourne la liste des partages d'un fichier dont on founrnit le fileId dans la table des fichiers.
     *
     * @fileIaram $fileId
     *
     * @return array
     */
    public function getSharesByfileId($fileId)
    {
        $connexion = Application::connectPDO(SERVEUR, BASE, NOM, MDP);
        $sql = 'SELECT type, share.groupe, destinataire, commentaire, fileId, shareId, ';
        $sql .= 'dp.nom AS nomProf, dp.prenom AS prenomProf, de.nom AS nomEleve, de.prenom AS prenomEleve, ';
        $sql .= 'de.groupe AS classe, dc.libelle, pc.nomCours ';
        $sql .= 'FROM '.PFX.'thotShares as share ';
        $sql .= 'LEFT JOIN '.PFX.'profs AS dp ON dp.acronyme = share.destinataire ';
        $sql .= 'LEFT JOIN '.PFX.'eleves AS de ON de.matricule = share.destinataire ';
        $sql .= 'LEFT JOIN '.PFX."cours AS dc ON dc.cours = SUBSTR(share.groupe, 1, LOCATE ('-', share.groupe)-1) ";
        $sql .= 'LEFT JOIN '.PFX.'profsCours AS pc ON pc.coursGrp = share.groupe ';
        $sql .= "WHERE fileId = '$fileId' ";
        $sql .= 'ORDER BY type, share.groupe, destinataire ';

        $resultat = $connexion->query($sql);
        $liste = array();
        if ($resultat) {
            $resultat->setFetchMode(PDO::FETCH_ASSOC);
            while ($ligne = $resultat->fetch()) {
                $liste[] = $ligne;
            }
        }
        Application::DeconnexionPDO($connexion);

        return $liste;
    }

    /**
     * renvoie le path et le fileName d'un document dont on fournit le fileId.
     *
     * @param $fileId
     *
     * @return array
     */
    public function getSharedfileById($fileId)
    {
        $connexion = Application::connectPDO(SERVEUR, BASE, NOM, MDP);
        $sql = 'SELECT path, fileName, acronyme, shareId ';
        $sql .= 'FROM '.PFX.'thotFiles AS file ';
        $sql .= 'JOIN '.PFX.'thotShares AS share ON share.fileId = file.fileId ';
        $sql .= 'WHERE file.fileId =:fileId ';

        $requete = $connexion->prepare($sql);
        $data = array(':fileId' => $fileId);
        $resultat = $requete->execute($data);
        $file = array();
        if ($resultat) {
            $file = $requete->fetch();
        }
        Application::deconnexionPDO($connexion);

        return $file;
    }

    /**
     * Enregistrement du partage d'un fichier.
     *
     * @param $post : contenu du formulaire
     *
     * @return int : identifiant de l'enregistrement du ficher partagé
     */
    public function share($post, $acronyme)
    {
        $fileName = $post['fileName'];
        $path = $post['path'];
        // définir un fileId ou retrouver le fileId existant
        $fileId = $this->findFileId($path, $fileName, $acronyme);

        $type = $post['type'];
        $groupe = $post['groupe'];
        $commentaire = $post['commentaire'];
        $tous = isset($post['TOUS']) ? $post['TOUS'] : null;
        $membres = isset($post['membres']) ? $post['membres'] : null;

        $connexion = Application::connectPDO(SERVEUR, BASE, NOM, MDP);
        // enregistrer les partages
        $sql = 'INSERT IGNORE INTO '.PFX.'thotShares ';
        $sql .= 'SET fileId=:fileId, type=:type, groupe=:groupe, destinataire=:destinataire, commentaire=:commentaire ';

        $requete = $connexion->prepare($sql);
        $resultat = 0;
        $data = array(':fileId' => $fileId, ':type' => $type, ':groupe' => $groupe, ':commentaire' => $commentaire);

        // si le destinataire est tout le groupe
        if ($tous != null) {
            $data[':destinataire'] = 'all';
            $resultat = $requete->execute($data);
        } else {
            // sinon, indiquer chaque membre du groupe comme destinataire
            if ($membres != null) {
                foreach ($membres as $unMembre) {
                    $data[':destinataire'] = $unMembre;
                    $resultat += $requete->execute($data);
                }
            }
        }
        Application::DeconnexionPDO($connexion);

        return $fileId;
    }

    /**
     * supprimer tous les partages d'un fichier dont on fournit le path, le fileName et l'acronyme de l'utilisateur.
     *
     * @param $path
     * @param $fileName
     * @param $acronyme
     *
     * @return int : le nombre d'effacements dans la BD
     */
    public function delAllShares($path, $fileName, $acronyme)
    {
        $fileId = $this->findFileId($path, $fileName, $acronyme);

        $connexion = Application::connectPDO(SERVEUR, BASE, NOM, MDP);
        $sql = 'DELETE FROM '.PFX.'thotShares ';
        $sql .= "WHERE fileId='$fileId' ";
        $resultat = $connexion->exec($sql);
        Application::DeconnexionPDO($connexion);

        return $resultat;
    }

    /**
     * Vérifier que l'utilisateur $acronyme est propriétaire du document $fileId.
     *
     * @param $fileId : l'identifiant du document dans la BD
     * @param $acronyme : l'identifiant du possible propriétaire
     *
     * @return bool
     */
    private function verifProprietaire($fileId, $acronyme)
    {
        $connexion = Application::connectPDO(SERVEUR, BASE, NOM, MDP);
        $sql = 'SELECT fileId, acronyme ';
        $sql .= 'FROM '.PFX.'thotFiles ';
        $sql .= "WHERE fileId='$fileId' AND acronyme='$acronyme' ";
        $resultat = $connexion->query($sql);
        $verif = false;
        if ($resultat) {
            $ligne = $resultat->fetch();
            if (($ligne['fileId'] == $fileId) && ($ligne['acronyme'] == $acronyme)) {
                $verif = true;
            } else {
                $verif = false;
            }
        }
        Application::DeconnexionPDO($connexion);

        return $verif;
    }

    /**
     * Vérifier que l'utilisateur $acronyme est propriétaire du partage $shareId.
     *
     * @param $acronyme : identifaint de l'utilisateur
     * @param $shareId : identifiant du partage
     *
     * @return $bool
     */
    public function verifProprietaireShare($shareId, $acronyme)
    {
        $connexion = Application::connectPDO(SERVEUR, BASE, NOM, MDP);
        $sql = 'SELECT acronyme, shareId ';
        $sql .= 'FROM '.PFX.'thotShares AS share ';
        $sql .= 'JOIN '.PFX.'thotFiles AS files ON files.fileId = share.fileId ';
        $sql .= "WHERE shareId = '$shareId' AND acronyme = '$acronyme' ";

        $resultat = $connexion->query($sql);
        $verif = false;
        if ($resultat) {
            $resultat->setFetchMode(PDO::FETCH_ASSOC);
            $ligne = $resultat->fetch();
            if ($ligne['acronyme'] == $acronyme) {
                $verif = true;
            }
        }
        Application::DeconnexionPDO($connexion);

        return $verif;
    }

    /**
     * Enregistrement d'une édition d'un commentaire de fichier dont on fournit le shareId.
     *
     * @param $commentaire : stringType
     * @param $shareId : l'identifiant du partage
     *
     * @return string : le commentaire enregistré
     */
    public function saveEditedComment($commentaire, $shareId)
    {
        $connexion = Application::connectPDO(SERVEUR, BASE, NOM, MDP);
        $sql = 'UPDATE '.PFX.'thotShares ';
        $sql .= 'SET commentaire=:commentaire ';
        $sql .= 'WHERE shareId=:shareId ';
        $requete = $connexion->prepare($sql);
        $data = array(':commentaire' => $commentaire, ':shareId' => $shareId);
        $resultat = $requete->execute($data);
        Application::DeconnexionPDO($connexion);
        if ($resultat == 1) {
            return $commentaire;
        } else {
            return '';
        }
    }

    /**
     * clôture le partage d'un fichier dont on fournit l'identifiant et l'acronyme du propriétaire.
     *
     * @param $shareId : identifiant du fichier partagé
     * @param $acronyme : identifiant du propriétaire
     *
     * @return bool : true si l'opération s'est bien passée
     */
    public function endShare($shareId, $acronyme)
    {
        $end = false;
        if ($this->verifProprietaire($shareId, $acronyme)) {
            $connexion = Application::connectPDO(SERVEUR, BASE, NOM, MDP);
            $sql = 'DELETE FROM '.PFX.'thotShares ';
            $sql .= "WHERE shareId='$shareId' ";
            $resultat = $connexion->exec($sql);
            if ($resultat == 1) {
                $end = true;
            }
        }
        Application::DeconnexionPDO($connexion);

        return $end;
    }

    /**
     * supprime la mention d'un fichier dans la BD, après effacement du fichier.
     *
     * @param $path: chemin vers le fichier
     * @param $fileName
     * @param $acronyme : sécurité
     *
     * @return int : le nombre de fichiers supprimés (0 ou 1)
     */
    public function clearBD($path, $fileName, $acronyme)
    {
        $connexion = Application::connectPDO(SERVEUR, BASE, NOM, MDP);
        $sql = 'DELETE FROM '.PFX.'thotFiles ';
        $sql .= 'WHERE path=:path AND fileName=:fileName AND acronyme=:acronyme ';
        $requete = $connexion->prepare($sql);
        $data = array(':path' => $path, ':fileName' => $fileName, ':acronyme' => $acronyme);
        $resultat = $requete->execute($data);
        Application::DeconnexionPDO($connexion);

        return $resultat;
    }

    /**
     * vérifie que le ficher référencé par $fileId est partagé et détenu par l'utilisateur $acronyme.
     *
     * @param $fileId
     * @param $acronyme
     *
     * @return bool
     */
    public function fileIdIsSharedBy($fileId, $acronyme)
    {
        $connexion = Application::connectPDO(SERVEUR, BASE, NOM, MDP);
        $sql = 'SELECT shares.fileId, acronyme ';
        $sql .= 'FROM '.PFX.'thotShares AS shares ';
        $sql .= 'JOIN '.PFX.'thotFiles AS files ON shares.fileId = files.fileId ';
        $sql .= "WHERE shares.fileId = '$fileId' AND acronyme = '$acronyme' ";
        $resultat = $connexion->query($sql);
        $test = false;
        if (resultat) {
        }
        Application::DeconnexionPDO($connexion);

        return;
    }

    /**
     * clôture le partage d'un fichier dont on fournit l'identifiant de partage ($shareId);.
     *
     * @param $shareId
     * @param $acronyme (pour vérifier que le fichier appartient bien à l'utilisateur actif)
     *
     * @return int : le nombre d'enregistrements supprimés (0 en cas d'échec ou 1)
     */
    public function unShareByShareId($shareId, $acronyme)
    {
        if ($this->verifProprietaireShare($shareId, $acronyme)) {
            $connexion = Application::connectPDO(SERVEUR, BASE, NOM, MDP);
            $sql = 'DELETE FROM '.PFX.'thotShares ';
            $sql .= "WHERE shareId = '$shareId' ";
            $resultat = $connexion->exec($sql);
            Application::DeconnexionPDO($connexion);

            return $resultat;
        } else {
            die('Ce fichier ne vous appartient pas');
        }
    }

    /**
     * clôture le partage d'un fichier dont on fournit l'identifiant $fileId.
     *
     * @param $fileId
     *
     * @return bool : opération réussie?
     */
    public function unShareByFileId($fileId)
    {
        $connexion = Application::connectPDO(SERVEUR, BASE, NOM, MDP);
        $sql = 'DELETE FROM '.PFX.'thotShares ';
        $sql .= "WHERE fileId = '$fileId' ";
        $resultat = $connexion->exec($sql);
        Application::DeconnexionPDO($connexion);

        return $resultat;
    }

    /**
     * clôture le partage de tous les fichiers de la liste fournie.
     *
     * @param $fileList : liste des fichiers array('path'=>..., 'fileName'=>...)
     * @param $acronyme : propriétaire du fichier (sécurité)
     *
     * @return int : nombre de clôtures réalisées
     */
    public function unShareAllFiles($fileList, $acronyme)
    {
        $nb = 0;
        $ds = DIRECTORY_SEPARATOR;
        $connexion = Application::connectPDO(SERVEUR, BASE, NOM, MDP);
        $sql1 = 'DELETE FROM '.PFX.'thotShares ';
        $sql1 .= 'WHERE fileId=:fileId, shareId=:shareId, acronyme=:acronyme ';
        $requeteShares = $connexion->prepare($sql1);

        $sql2 = 'DELETE FROM '.PFX.'thotFiles ';
        $sql2 .= 'WHERE fileId=:fileId ';
        $requeteFile = $connexion->prepare($sql2);
        foreach ($fileList as $share) {
            $path = substr($share['path'], strpos($share['path'], 'upload'.$ds.$acronyme) + strlen('upload'.$ds.$acronyme));
            $shareList = $this->requestFileDetails($path, $share['fileName'], $acronyme);
            if ($shareList != null) {
                foreach ($shareList as $oneShare) {
                    $data = array(
                            ':fileId' => $oneShare['fileId'],
                            ':shareId' => $oneShare['shareId'],
                            ':acronyme' => $acronyme,
                        );
                    $nb += $requeteShares->execute($data);
                }
                $data = array(
                    ':fileId' => $oneShare['fileId'],
                );
                $nb += $requeteFile->execute($data);
            }
        }
        Application::DeconnexionPDO($connexion);

        return $nb;
    }

    /**
     * retourne la liste des partages pour un fichier dont on indique le path et le fileName pour l'utilisateur donné.
     *
     * @param $path
     * @param $fileName
     * @param $acronyme
     *
     * @return array
     */
    public function listShares($path, $fileName, $acronyme)
    {
        $connexion = Application::connectPDO(SERVEUR, BASE, NOM, MDP);
        $sql = 'SELECT files.fileId, type, share.groupe, destinataire, commentaire, de.nom, de.prenom, ';
        $sql .= 'profs.nom AS nomProf, profs.prenom AS prenomProf ';
        $sql .= 'FROM '.PFX.'thotFiles AS files ';
        $sql .= 'JOIN '.PFX.'thotShares AS share ON files.fileId = share.fileId ';
        $sql .= 'LEFT JOIN '.PFX.'eleves AS de ON de.matricule = share.destinataire ';
        $sql .= 'LEFT JOIN '.PFX.'profs AS profs ON profs.acronyme = share.destinataire ';
        $sql .= 'WHERE files.acronyme=:acronyme AND path=:path AND fileName=:fileName ';
        $sql .= 'ORDER BY type, groupe, destinataire ';
        $requete = $connexion->prepare($sql);
        $data = array(':acronyme' => $acronyme, ':path' => $path, ':fileName' => $fileName);
        $resultat = $requete->execute($data);

        $liste = array();
        if ($resultat) {
            $requete->setFetchMode(PDO::FETCH_ASSOC);
            while ($ligne = $requete->fetch()) {
                $type = $ligne['type'];
                switch ($type) {
                    case 'ecole':
                        $liste[] = 'Tous les élèves';
                        break;
                    case 'niveau':
                        $liste[] = 'Tous les élèves de '.$ligne['destinataire'].'e';
                        break;
                    case 'prof':
                        if ($ligne['destinataire'] == 'all') {
                            $liste[] = 'Tous les collègues';
                        } else {
                            $liste[] = sprintf('collègue: %s %s', $ligne['prenomProf'], $ligne['nomProf']);
                        }
                        break;
                    case 'classe':
                        if ($ligne['destinataire'] == 'all') {
                            $liste[] = 'Tous les élèves de '.$ligne['groupe'];
                        } else {
                            $liste[] = sprintf('%s: %s %s', $ligne['groupe'], $ligne['nom'], $ligne['prenom']);
                        }
                        break;
                    case 'cours':
                        if ($ligne['destinataire'] == 'all') {
                            $liste[] = 'Tous les élèves du cours '.$ligne['groupe'];
                        } else {
                            $liste[] = sprintf('%s: %s %s', $ligne['groupe'], $ligne['nom'], $ligne['prenom']);
                        }
                        break;
                    default:
                        // wtf;
                        break;
                    }
            }
        }
        Application::DeconnexionPDO($connexion);

        return $liste;
    }

    /**
     * renvoie tous les noms de fichiers (avec les répertoires) inclus dans l'arboresence indiquée.
     *
     * @param $root : emplacement du répertoire d'upload sur le serveur
     * @param $upload:
     *
     * @return array
     */
    public function getAllFilesFrom($root, $upload)
    {
        $path = $root.$upload;
        $directory = new \RecursiveDirectoryIterator($path);
        $iterator = new \RecursiveIteratorIterator($directory);
        $files = array();
        foreach ($iterator as $info) {
            $dir = $info->getPath();
            $fileName = $info->getFilename();
            if ($fileName != '..') {
                $files[] = array('path' => $dir, 'fileName' => $fileName);
            }
        }

        return $files;
    }

    /**
     * renvoie le catalogue des fichiers partagés avec un prof.
     *
     * @param $acronyme
     *
     * @return array
     */
    public function sharedWith($acronyme)
    {
        $connexion = Application::connectPDO(SERVEUR, BASE, NOM, MDP);
        $sql = 'SELECT share.shareId, share.fileId, groupe, destinataire, commentaire, path, fileName, ';
        $sql .= 'files.acronyme, nom, prenom ';
        $sql .= 'FROM '.PFX.'thotShares AS share ';
        $sql .= 'JOIN '.PFX.'thotFiles AS files ON files.fileId = share.fileId ';
        $sql .= 'JOIN '.PFX.'profs AS p ON p.acronyme = files.acronyme ';
        $sql .= "WHERE (share.groupe = 'prof' AND destinataire = 'all') ";
        $sql .= "OR destinataire = '$acronyme' ";
        $sql .= 'ORDER BY nom, prenom, fileName ';
        $resultat = $connexion->query($sql);
        $liste = array();
        if ($resultat) {
            $resultat->setFetchMode(PDO::FETCH_ASSOC);
            while ($ligne = $resultat->fetch()) {
                $id = $ligne['shareId'];
                $liste[$id] = $ligne;
            }
        }
        Application::DeconnexionPDO($connexion);

        return $liste;
    }
}