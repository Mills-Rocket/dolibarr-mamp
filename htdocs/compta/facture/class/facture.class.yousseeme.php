/**
	 *	Get object from database. Get also lines.
	 *
	 *	@param      int		$rowid       		Id of object to load
	 * 	@param		string	$ref				Reference of invoice
	 * 	@param		string	$ref_ext			External reference of invoice
	 * 	@param		int		$notused			Not used
	 *  @param		bool	$fetch_situation	Load also the previous and next situation invoice into $tab_previous_situation_invoice and $tab_next_situation_invoice
	 *	@return     int         				>0 if OK, <0 if KO, 0 if not found
	 */
	public function fetch($rowid, $ref = '', $ref_ext = '', $notused = 0, $fetch_situation = false)
	{
		if (empty($rowid) && empty($ref) && empty($ref_ext)) {
			return -1;
		}

		$sql = 'SELECT f.rowid, f.entity, f.ref, f.ref_client, f.ref_ext, f.type, f.subtype, f.fk_soc';
		$sql .= ', f.total_tva, f.localtax1, f.localtax2, f.total_ht, f.total_ttc, f.revenuestamp';
		$sql .= ', f.datef as df, f.date_pointoftax';
		$sql .= ', f.date_lim_reglement as dlr';
		$sql .= ', f.datec as datec';
		$sql .= ', f.date_valid as datev';
		$sql .= ', f.tms as datem';
		$sql .= ', f.note_private, f.note_public, f.fk_statut as status, f.paye, f.close_code, f.close_note, f.fk_user_author, f.fk_user_valid, f.fk_user_modif, f.model_pdf, f.last_main_doc';
		$sql .= ', f.fk_facture_source, f.fk_fac_rec_source';
		$sql .= ', f.fk_mode_reglement, f.fk_cond_reglement, f.fk_projet as fk_project, f.extraparams';
		$sql .= ', f.situation_cycle_ref, f.situation_counter, f.situation_final';
		$sql .= ', f.fk_account';
		$sql .= ", f.fk_multicurrency, f.multicurrency_code, f.multicurrency_tx, f.multicurrency_total_ht, f.multicurrency_total_tva, f.multicurrency_total_ttc";
		$sql .= ', p.code as mode_reglement_code, p.libelle as mode_reglement_libelle';
		$sql .= ', c.code as cond_reglement_code, c.libelle as cond_reglement_libelle, c.libelle_facture as cond_reglement_libelle_doc';
		$sql .= ', f.fk_incoterms, f.location_incoterms';
		$sql .= ', f.module_source, f.pos_source';
		$sql .= ", i.libelle as label_incoterms";
		$sql .= ", f.retained_warranty as retained_warranty, f.retained_warranty_date_limit as retained_warranty_date_limit, f.retained_warranty_fk_cond_reglement as retained_warranty_fk_cond_reglement";
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facture as f';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_payment_term as c ON f.fk_cond_reglement = c.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as p ON f.fk_mode_reglement = p.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_incoterms as i ON f.fk_incoterms = i.rowid';

		if ($rowid) {
			$sql .= " WHERE f.rowid = ".((int) $rowid);
		} else {
			$sql .= ' WHERE f.entity IN ('.getEntity('invoice').')'; // Don't use entity if you use rowid
			if ($ref) {
				$sql .= " AND f.ref = '".$this->db->escape($ref)."'";
			}
			if ($ref_ext) {
				$sql .= " AND f.ref_ext = '".$this->db->escape($ref_ext)."'";
			}
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->entity = $obj->entity;

				$this->ref					= $obj->ref;
				$this->ref_client			= $obj->ref_client;
				$this->ref_customer			= $obj->ref_client;
				$this->ref_ext				= $obj->ref_ext;
				$this->type					= $obj->type;
				$this->subtype				= $obj->subtype;
				$this->date					= $this->db->jdate($obj->df);
				$this->date_pointoftax		= $this->db->jdate($obj->date_pointoftax);
				$this->date_creation        = $this->db->jdate($obj->datec);
				$this->date_validation		= $this->db->jdate($obj->datev);
				$this->date_modification    = $this->db->jdate($obj->datem);
				$this->datem                = $this->db->jdate($obj->datem);
				$this->total_ht				= $obj->total_ht;
				$this->total_tva			= $obj->total_tva;
				$this->total_localtax1		= $obj->localtax1;
				$this->total_localtax2		= $obj->localtax2;
				$this->total_ttc			= $obj->total_ttc;
				$this->revenuestamp         = $obj->revenuestamp;
				$this->paye                 = $obj->paye;
				$this->close_code			= $obj->close_code;
				$this->close_note			= $obj->close_note;

				$this->socid = $obj->fk_soc;
				$this->thirdparty = null; // Clear if another value was already set by fetch_thirdparty

				$this->fk_project = $obj->fk_project;
				$this->project = null; // Clear if another value was already set by fetch_projet

				$this->statut = $obj->status;	// deprecated
				$this->status = $obj->status;

				$this->date_lim_reglement = $this->db->jdate($obj->dlr);
				$this->mode_reglement_id	= $obj->fk_mode_reglement;
				$this->mode_reglement_code	= $obj->mode_reglement_code;
				$this->mode_reglement		= $obj->mode_reglement_libelle;
				$this->cond_reglement_id	= $obj->fk_cond_reglement;
				$this->cond_reglement_code	= $obj->cond_reglement_code;
				$this->cond_reglement		= $obj->cond_reglement_libelle;
				$this->cond_reglement_doc = $obj->cond_reglement_libelle_doc;
				$this->fk_account = ($obj->fk_account > 0) ? $obj->fk_account : null;

                // Fetch the bank account extra field (URL)
                if ($this->fk_account > 0) {
                    $sql_extrafield = "SELECT link FROM ".MAIN_DB_PREFIX."bank_account_extrafields WHERE fk_object = ".$this->fk_account;
                    $resql_extrafield = $this->db->query($sql_extrafield);
                    if ($resql_extrafield) {
                        $obj_extrafield = $this->db->fetch_object($resql_extrafield);
                        if ($obj_extrafield) {
                            $this->bank_account_link = $obj_extrafield->link; // Assuming 'link' is the column storing the URL
                        }
                    }
                }


				$this->fk_facture_source	= $obj->fk_facture_source;
				$this->fk_fac_rec_source	= $obj->fk_fac_rec_source;
				$this->note = $obj->note_private; // deprecated
				$this->note_private = $obj->note_private;
				$this->note_public			= $obj->note_public;
				$this->user_creation_id     = $obj->fk_user_author;
				$this->user_validation_id   = $obj->fk_user_valid;
				$this->user_modification_id = $obj->fk_user_modif;
				$this->fk_user_author       = $obj->fk_user_author;
				$this->fk_user_valid        = $obj->fk_user_valid;
				$this->fk_user_modif        = $obj->fk_user_modif;
				$this->model_pdf = $obj->model_pdf;
				$this->last_main_doc = $obj->last_main_doc;
				$this->situation_cycle_ref  = $obj->situation_cycle_ref;
				$this->situation_counter    = $obj->situation_counter;
				$this->situation_final      = $obj->situation_final;
				$this->retained_warranty    = $obj->retained_warranty;
				$this->retained_warranty_date_limit         = $this->db->jdate($obj->retained_warranty_date_limit);
				$this->retained_warranty_fk_cond_reglement  = $obj->retained_warranty_fk_cond_reglement;

				$this->extraparams = !empty($obj->extraparams) ? (array) json_decode($obj->extraparams, true) : array();

				//Incoterms
				$this->fk_incoterms         = $obj->fk_incoterms;
				$this->location_incoterms   = $obj->location_incoterms;
				$this->label_incoterms = $obj->label_incoterms;

				$this->module_source = $obj->module_source;
				$this->pos_source = $obj->pos_source;

				// Multicurrency
				$this->fk_multicurrency 		= $obj->fk_multicurrency;
				$this->multicurrency_code = $obj->multicurrency_code;
				$this->multicurrency_tx 		= $obj->multicurrency_tx;
				$this->multicurrency_total_ht = $obj->multicurrency_total_ht;
				$this->multicurrency_total_tva 	= $obj->multicurrency_total_tva;
				$this->multicurrency_total_ttc 	= $obj->multicurrency_total_ttc;

				if (($this->type == self::TYPE_SITUATION || ($this->type == self::TYPE_CREDIT_NOTE && $this->situation_cycle_ref > 0)) && $fetch_situation) {
					$this->fetchPreviousNextSituationInvoice();
				}

				// Retrieve all extrafield
				// fetch optionals attributes and labels
				$this->fetch_optionals();

				// Lines
				$this->lines = array();

				$result = $this->fetch_lines();
				if ($result < 0) {
					$this->error = $this->db->error();
					return -3;
				}

				$this->db->free($resql);

				return 1;
			} else {
				$this->error = 'Invoice with id='.$rowid.' or ref='.$ref.' or ref_ext='.$ref_ext.' not found';

				dol_syslog(__METHOD__.$this->error, LOG_WARNING);
				return 0;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}