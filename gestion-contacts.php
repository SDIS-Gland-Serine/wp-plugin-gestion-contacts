<?php
/*
Plugin Name: Gestion des Contacts
Plugin URI: https://www.example.com/
Description: Plugin de gestion des contacts pour WordPress
Version: 1.0
Author: Hervé Viquerat / ChatGPT
Author URI: https://www.example.com/
License: GPLv2 or later
Droit d'accès : contacts_read / contacts_edit
*/

// Crée le menu et les sous-menus pour gérer les contacts

function gestion_contacts_activation() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'contacts';

    // Vérifie si la table n'existe pas déjà
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        // Requête SQL pour créer la table
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            nom varchar(100) NOT NULL,
            prenom varchar(100),
            email varchar(100),
            telephone varchar(20),
            categorie varchar(20),
            sous_categorie varchar(100),
            fonction varchar(200),
            prioritaire tinyint(1) default 0 NOT NULL,
            deleted tinyint(1) default 0 NOT NULL,
            syndic tinyint(1) default 0 NOT NULL,
            CI_DIR tinyint(1) default 0 NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Inclut le fichier nécessaire pour utiliser la fonction dbDelta()
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'gestion_contacts_activation');


add_action('admin_menu', 'gestion_contacts_menu');



function gestion_contacts_menu() {
    add_menu_page('SDIS Contacts','SDIS Contacts','contacts_read','gestion-contacts','gestion_contacts_liste','dashicons-id',30);
    add_submenu_page('gestion-contacts','Liste','Liste','contacts_read','gestion_contacts_liste','gestion_contacts_liste');
    add_submenu_page('gestion-contacts','Ajouter','Ajouter','contacts_edit','gestion-contacts-ajouter','gestion_contacts_ajouter');
    add_submenu_page( null,'Editer','Editer','contacts_edit','gestion-contacts-editer','gestion_contacts_editer');
    add_submenu_page( null,'Afficher','Afficher','contacts_read','gestion_contacts_details','gestion_contacts_details');
    add_submenu_page( null,'Supprimer','Supprimer','contacts_edit','gestion_contacts_supprimer_contact','gestion_contacts_supprimer_contact');
    
    remove_submenu_page('gestion-contacts','gestion-contacts');
    
}



// Page de liste des contacts
function gestion_contacts_liste() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'contacts';

    // Récupère toutes les catégories distinctes
    $categories = $wpdb->get_col("SELECT DISTINCT categorie FROM $table_name order by categorie");

    // Récupère toutes les sous-catégories distinctes
    $sous_categories = $wpdb->get_col("SELECT DISTINCT sous_categorie FROM $table_name order by sous_categorie");

    // Vérifie si le formulaire de filtre a été soumis
    $selected_categorie = '';
    $selected_sous_categorie = '';
    if (isset($_POST['filtrer'])) {
        $selected_categorie = $_POST['categorie'];
        $selected_sous_categorie = $_POST['sous_categorie'];
    }

    // Construit la requête SQL en fonction des filtres
    $query = "SELECT * FROM $table_name WHERE deleted = 0";
    if (!empty($selected_categorie)) {
        $query .= $wpdb->prepare(" AND categorie = %s", $selected_categorie);
    }
    if (!empty($selected_sous_categorie)) {
        $query .= $wpdb->prepare(" AND sous_categorie = %s", $selected_sous_categorie);
    }

    // Récupère les contacts en utilisant la requête SQL construite
    if (current_user_can('contacts_cidir')){
        $contacts = $wpdb->get_results($query." ORDER BY prioritaire DESC, categorie, sous_categorie, fonction, nom" );
        }else{
        $contacts = $wpdb->get_results($query." AND CI_DIR = 0 ORDER BY prioritaire DESC, categorie, sous_categorie, fonction, nom" );
        }

    
    
    // Affiche la liste des contacts
    ?>
    <div class="wrap">
        <h2>Liste des contacts importants</h2>
        <?php if (current_user_can('contacts_cidir'))
        echo '<h3><span style="color: green;">&#9733;</span> seulement visible par les CI-DIR</h3>';?>

        <!-- Formulaire de filtre par catégorie et sous-catégorie -->
        <form method="post" action="" class="form_gestion_contacts">
			<span class="mobile_row">
				<label for="categorie">Filtrer par catégorie:</label>
				<select name="categorie" id="categorie">
					<option value="">Toutes les catégories</option>
					<?php foreach ($categories as $categorie) : ?>
						<option value="<?php echo esc_attr($categorie); ?>" <?php selected($selected_categorie, $categorie); ?>><?php echo esc_html($categorie); ?></option>
					<?php endforeach; ?>
				</select>
			</span>
			<span class="mobile_row">
				<label for="sous_categorie">Filtrer par sous-catégorie:</label>
				<select name="sous_categorie" id="sous_categorie">
					<option value="">Toutes les sous-catégories</option>
					<?php foreach ($sous_categories as $sous_categorie) : ?>
						<option value="<?php echo esc_attr($sous_categorie); ?>" <?php selected($selected_sous_categorie, $sous_categorie); ?>><?php echo esc_html($sous_categorie); ?></option>
					<?php endforeach; ?>
				</select>
			</span>
			<span class="mobile_row">
				<input type="submit" name="filtrer" class="button" value="Filtrer">
			</span>
        </form>


        
     
    <div class="wrap">
        <table class="wp-list-table widefat sortable" id="gestion_contacts">
            <thead>
                <tr>
                    <th>Catégorie</th>
                    <th>NOM Prénom</th>
                    <th>Sous-catégorie</th>
                    <th>Fonction</th>
                    <th>Téléphone</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contacts as $contact) : ?>
                    <tr>
                        <td><?php echo esc_html($contact->categorie); ?></td>
                        <td><?php if ($contact->prioritaire == 1) : ?><span style="color: red;">&#9733;</span><?php endif; ?>
                        <?php if ($contact->CI_DIR == 1) : ?><span style="color: green;">&#9733;</span><?php endif; ?>
                        <?php echo strtoupper($contact->nom); ?> <?php echo esc_html($contact->prenom); ?></td>
                        <td><?php echo esc_html($contact->sous_categorie); ?><?php if ($contact->syndic == 1) : ?><span> - Syndic</span><?php endif; ?></td>
                        <td><?php echo esc_html($contact->fonction); ?></td>
                        <td><?php echo format_tel($contact->telephone); ?></td>
                        <td><a href="?page=gestion_contacts_details&contact_id=<?php echo $contact->id; ?>">Afficher</a><?php if (current_user_can('contacts_edit')) : ?><a href="?page=gestion-contacts-editer&contact_id=<?php echo esc_attr($contact->id);?>"> / Editer<?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}



// Page d'ajout d'un contact
function gestion_contacts_ajouter() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'contacts';


    // Vérifie si le formulaire a été soumis
    if (isset($_POST['ajouter_contact'])) {
        // Récupère les données du formulaire
        $nom = sanitize_text_field($_POST['nom']);
        $prenom = sanitize_text_field($_POST['prenom']);
        $email = sanitize_email($_POST['email']);
        $telephone = sanitize_text_field($_POST['telephone']);
        $categorie = sanitize_text_field($_POST['categorie']);
        $sous_categorie = sanitize_text_field($_POST['sous_categorie']);
        $prioritaire = sanitize_text_field($_POST['prioritaire']);
        $fonction = sanitize_text_field($_POST['fonction']);
        $CI_DIR = sanitize_text_field($_POST['CI_DIR']);

        // Vérifie si les champs obligatoires sont renseignés
        if (empty($nom) || empty($telephone)) {
            echo '<div class="notice notice-error"><p>Tous les champs obligatoires doivent être renseignés.</p></div>';
        } else {
            // Insère les données du contact dans la base de données
            $wpdb->insert(
                $table_name,
                array(
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $email,
                    'telephone' => $telephone,
                    'categorie' => $categorie,
                    'sous_categorie' => $sous_categorie,
                    'prioritaire' => $prioritaire,
                    'fonction' => $fonction,
                    'CI_DIR' => $CI_DIR
                )
            );

            // Affiche un message de succès
            echo '<div class="notice notice-success"><p>Contact ajouté avec succès.</p></div>';

            // Réinitialise les valeurs des champs du formulaire
            $nom = '';
            $prenom = '';
            $email = '';
            $telephone = '';
            $categorie = '';
            $sous_categorie = '';
            $prioritaire = '';
            $fonction = '';
            $CI_DIR = '';
        }
    }

    // Affiche le formulaire d'ajout de contact
    ?>
    <div class="wrap">
        <h1>Ajouter un Contact</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th><label for="nom">Nom <span class="required">*</span></label></th>
                    <td><input type="text" name="nom" id="nom" value="<?php echo esc_attr($nom); ?>"></td>
                </tr>
                <tr>
                    <th><label for="nom">Prénom </label></th>
                    <td><input type="text" name="prenom" id="prenom" value="<?php echo esc_attr($prenom); ?>"></td>
                </tr>
                <tr>
                    <th><label for="email">Email </label></th>
                    <td><input type="email" name="email" id="email" value="<?php echo esc_attr($email); ?>"></td>
                </tr>
                <tr>
                    <th><label for="telephone">Téléphone <span class="required">*</span></label></th>
                    <td><input type="text" name="telephone" id="telephone" value="<?php echo esc_attr($telephone); ?>"></td>
                </tr>
                <tr>
                    <th><label for="categorie">Catégorie</label></th>
                    <td><input type="text" name="categorie" id="categorie" value="<?php echo esc_attr($categorie); ?>"></td>
                </tr>
                <tr>
                    <th><label for="sous_categorie">Sous-catégorie</label></th>
                    <td><input type="text" name="sous_categorie" id="sous_categorie" value="<?php echo esc_attr($sous_categorie); ?>"></td>
                </tr>
                <tr>
                    <th><label for="prioritaire">Prioritaire</label></th>
                    <td><input type="text" name="prioritaire" id="prioritaire" value="<?php echo esc_attr($prioritaire); ?>"></td>
                </tr>
                <tr>
                    <th><label for="fonction">Fonction</label></th>
                    <td><input type="text" name="fonction" id="fonction" value="<?php echo esc_attr($fonction); ?>"></td>
                </tr>
                <tr>
                    <th><label for="CI_DIR">CI_DIR</label></th>
                    <td><input type="text" name="CI_DIR" id="CI_DIR" value="<?php echo esc_attr($CI_DIR); ?>"></td>
                </tr>

            </table>
            <p class="submit"><input type="submit" name="ajouter_contact" class="button button-primary" value="Ajouter"></p>
        </form>
    </div>
    <?php
}




// Page d'édition d'un contact
function gestion_contacts_editer() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'contacts';

    // Vérifie si l'ID du contact est passé en paramètre
    if (isset($_GET['contact_id'])) {
        $contact_id = intval($_GET['contact_id']);

        // Vérifie si le formulaire a été soumis
        if (isset($_POST['modifier_contact'])) {
            // Récupère les données du formulaire
            $nom = sanitize_text_field($_POST['nom']);
            $prenom = sanitize_text_field($_POST['prenom']);
            $email = sanitize_email($_POST['email']);
            $telephone = sanitize_text_field($_POST['telephone']);
            $categorie = sanitize_text_field($_POST['categorie']);
            $sous_categorie = sanitize_text_field($_POST['sous_categorie']);
            $prioritaire = sanitize_text_field($_POST['prioritaire']); 
            $fonction = sanitize_text_field($_POST['fonction']);
            $syndic = sanitize_text_field($_POST['syndic']);
            $CI_DIR = sanitize_text_field($_POST['CI_DIR']); 

            // Vérifie si les champs obligatoires sont renseignés
            if (empty($nom) || empty($categorie)) {
                echo '<div class="notice notice-error"><p>Tous les champs obligatoires doivent être renseignés.</p></div>';
            } else {
                // Met à jour les données du contact dans la base de données
                $wpdb->update(
                    $table_name,
                    array(
                        'nom' => $nom,
                        'prenom' => $prenom,
                        'email' => $email,
                        'telephone' => $telephone,
                        'categorie' => $categorie,
                        'sous_categorie' => $sous_categorie,
                        'prioritaire' => $prioritaire,
                        'fonction' => $fonction,
                        'syndic' => $syndic,
                        'CI_DIR' => $CI_DIR
                    ),
                    array('id' => $contact_id)
                );

                // Affiche un message de succès
                echo '<div class="notice notice-success"><p>Contact modifié avec succès.</p></div>';
            }
        }

        // Récupère les données du contact à partir de l'ID
        $contact = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $contact_id));

        // Vérifie si le contact existe
        if ($contact) {

            ?>
            <div class="wrap">
                <h1>Éditer un Contact</h1>
                <form method="post" action="">
                    <table class="form-table">
                        <tr>
                            <th><label for="nom">Nom <span class="required">*</span></label></th>
                            <td><input type="text" name="nom" id="nom" value="<?php echo esc_attr($contact->nom); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="nom">Prénom </label></th>
                            <td><input type="text" name="prenom" id="prenom" value="<?php echo esc_attr($contact->prenom); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="email">Email </label></th>
                            <td><input type="email" name="email" id="email" value="<?php echo esc_attr($contact->email); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="telephone">Téléphone</label></th>
                            <td><input type="text" name="telephone" id="telephone" value="<?php echo esc_attr($contact->telephone); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="categorie">Catégorie <span class="required">*</span></label></th>
                            <td><input type="text" name="categorie" id="categorie" value="<?php echo esc_attr($contact->categorie); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="sous_categorie">Sous-catégorie</label></th>
                            <td><input type="text" name="sous_categorie" id="sous_categorie" value="<?php echo esc_attr($contact->sous_categorie); ?>"></td>
                        </tr>
<?php /*                         <tr>
                            <th><label for="prioritaire">Prioritaire</label></th>
                            <td><input type="checkbox" name="prioritaire" id="prioritaire" <?php checked($contact->prioritaire, 1); ?>></td>
                        </tr>*/ ?>
                        <tr>
                            <th><label for="prioritaire">Prioritaire</label></th>
                            <td><input type="text" name="prioritaire" id="prioritaire" size="1" value="<?php echo esc_attr($contact->prioritaire); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="fonction">Fonction</label></th>
                            <td><input type="text" name="fonction" id="fonction" size="200" value="<?php echo esc_attr($contact->fonction); ?>"></td>
                        </tr>
<?php /*                        <tr>
                            <th><label for="syndic">Syndic</label></th>
                            <td><input type="checkbox" name="syndic" id="syndic" <?php checked($contact->syndic, 1); ?>></td>
                        </tr>
                        <tr>
                            <th><label for="CI_DIR">CI_DIR</label></th>
                            <td><input type="checkbox" name="CI_DIR" id="CI_DIR" <?php checked($contact->CI_DIR, 1); ?>></td>
                        </tr> */ ?>
                        <tr>
                            <th><label for="syndic">Syndic</label></th>
                            <td><input type="text" name="syndic" id="CI_DIR" size="1" value="<?php echo esc_attr($contact->syndic); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="CI_DIR">CI_DIR</label></th>
                            <td><input type="text" name="CI_DIR" id="CI_DIR" size="1" value="<?php echo esc_attr($contact->CI_DIR); ?>"></td>
                        </tr>
 
                        
                    </table>
                    <div class="button-group">
                        <input type="submit" name="modifier_contact" class="button button-primary" value="Modifier">
                        <a class="button" href="?page=gestion_contacts_liste">Retour</a>
                        <a class="button" href="?page=gestion_contacts_supprimer_contact&contact_id=<?php echo esc_attr($contact->id);?>">Supprimer</a>
                    </div>
                    
                </form>
            </div>
            <?php
        } else {
            // Affiche un message d'erreur si le contact n'existe pas
            echo '<div class="notice notice-error"><p>Contact non trouvé.</p></div>';
        }
    } else {
        // Affiche un message d'erreur si l'ID du contact n'est pas spécifié
        echo '<div class="notice notice-error"><p>ID du contact manquant.</p></div>';
    }
}




// Page d'affichage d'un contact
function gestion_contacts_details() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'contacts';

    // Vérifie si l'ID du contact est passé en paramètre de l'URL
    if (isset($_GET['contact_id'])) {
        $contact_id = intval($_GET['contact_id']);
        $contact = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $contact_id));

        // Vérifie si le contact existe dans la base de données
        if ($contact) {
            ?>
            <div class="wrap">
                <h2>Afficher détails du Contact</h2>
                <table class="form-table">
                    <tr>
                        <th>Nom Prénom</th>
                        <td><b><?php echo esc_html($contact->nom)?></b><?php echo ' '; echo esc_html($contact->prenom); ?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><a href="mailto:'<?php echo esc_html($contact->email); ?>'"><?php echo esc_html($contact->email); ?></a></td>;
                    </tr>
                    <tr>
                        <th>Téléphone</th>
                        <td><?php echo format_tel($contact->telephone); ?></td>
                    </tr>
                    <tr>
                        <th>Catégorie</th>
                        <td><?php echo esc_html($contact->categorie); ?></td>
                    </tr>
                    <tr>
                        <th>Sous-catégorie</th>
                        <td><?php echo esc_html($contact->sous_categorie); ?><?php if ($contact->syndic == 1) : ?><span> - Syndic</span><?php endif; ?></td>
                    </tr>
                    <tr>
                        <th>Fonction</th>
                        <td><?php echo esc_html($contact->fonction); ?></td>
                    </tr><tr>
                        <th>Prioritaire</th>
                        <td><?php if ($contact->prioritaire) : ?><span class="dashicons dashicons-star-filled"></span><?php endif; ?></td>
                    </tr>
                    </tr><tr>
                        <th>CI_DIR</th>
                        <td><?php if ($contact->CI_DIR) : ?><span class="dashicons dashicons-star-filled"></span><?php endif; ?></td>
                    </tr>
                </table>
                <div class="button-group">
                        <?php if (current_user_can('contacts_edit')) : ?><a class="button" href="?page=gestion-contacts-editer&contact_id=<?php echo esc_attr($contact->id);?>">Editer<?php endif; ?>
                        <a class="button" href="?page=gestion_contacts_liste">Retour</a>
                </div>
            </div>
            <?php
        } else {
            echo 'Le contact demandé n\'existe pas.';
        }
    } else {
        echo 'Aucun contact spécifié.';
    }
}


// Fonction de suppression d'un contact
function gestion_contacts_supprimer_contact() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'contacts';

    // Vérifie si l'ID du contact est passé en paramètre de l'URL
    if (isset($_GET['contact_id'])) {
        $contact_id = intval($_GET['contact_id']);

        // Supprime le contact de la base de données
        //$wpdb->delete($table_name, array('id' => $contact_id));
        $wpdb->update(
            $table_name,
            array(
                'deleted' => 1
            ),
            array('id' => $contact_id)
        );

        // Redirige vers la liste des contacts après la suppression
        wp_redirect(admin_url('admin.php?page=gestion_contacts_liste'));
        exit;
    } 
}

function widgetOrganigramme() {
    global $wpdb;
	$exercices = $wpdb->get_results( 
		"
		SELECT * 
		FROM organigramme
		WHERE email IS NOT NULL AND email <> ''
		ORDER BY dicastereID ASC, position ASC
		"
	);
	echo '<table>';
	foreach ( $exercices as $i => $row ) {
		echo '<tr>';
		echo '<td style="padding-bottom:5px;"><strong>'.$row->fonction.'</strong><br><span style="font-size:0.8em;top:-4px;position:relative;color:#B9B9B9;">'.$row->nom1.'</span></td>';
		echo '<td style="vertical-align:top;"><a href="mailto:'.$row->email.'">'.$row->email.'</td>';
		echo '</tr>';
	}	
	echo '</table>';
}

/**
 * Add function as widget to the dashboard.
 */
function organigramme_add_dashboard_widgets() {

	wp_add_dashboard_widget(
                 'contact-orga',			// Widget slug.
                 'Contacts organigramme',	// Title.
                 'widgetOrganigramme'		// Display function.
        );
	
	global $wp_meta_boxes;

    $my_widget = $wp_meta_boxes['dashboard']['normal']['core']['Organigramme'];
    unset($wp_meta_boxes['dashboard']['normal']['core']['Organigramme']);
    $wp_meta_boxes['dashboard']['side']['core']['Organigramme'] = $my_widget;

}
add_action( 'wp_dashboard_setup', 'organigramme_add_dashboard_widgets' );

