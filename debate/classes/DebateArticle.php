<?php
/**
 * @package Campsite
 */
class DebateArticle extends DatabaseObject {
    /**
     * The column names used for the primary key.
     *
     * @var array
     */
    var $m_keyColumnNames = array('fk_debate_nr', 'fk_article_nr', 'fk_article_language_id');

    /**
     * Table name
     *
     * @var string
     */
    var $m_dbTableName = 'plugin_debate_article';

    /**
     * All column names in the table
      *
     * @var array
     */
    var $m_columnNames = array(
        // int - debate id
        'fk_debate_nr',

        // int - article number
        'fk_article_nr',

        // int - article language id
        'fk_article_language_id'
        );

    /**
     * Construct by passing in the primary key to access the
     * debate <-> publication relations
     *
     * @param int $p_debate_nr
     * @param int $p_article_language_id
     * @param int $p_article_nr
     */
    function DebateArticle($p_debate_nr = null, $p_article_language_id = null, $p_article_nr = null)
    {
        parent::DatabaseObject($this->m_columnNames);
        $this->m_data['fk_debate_nr'] = $p_debate_nr;
        $this->m_data['fk_article_language_id'] = $p_article_language_id;
        $this->m_data['fk_article_nr'] = $p_article_nr;

        if ($this->keyValuesExist()) {
            $this->fetch();
        }
    } // constructor


    /**
     * A way for internal functions to call the superclass create function.
     * @param array $p_values
     */
    function __create($p_values = null) { return parent::create($p_values); }


    /**
     * Create an link debate <-> publication record in the database.
     *
     * @return void
     */
    function create()
    {
        global $g_ado_db;

        // Create the record
        $success = parent::create();
        if (!$success) {
            return;
        }

        /*
        if (function_exists("camp_load_translation_strings")) {
            camp_load_translation_strings("api");
        }
        $logtext = getGS('Debate Id $1 created.', $this->m_data['IdDebate']);
        Log::Message($logtext, null, 31);
        */

        $CampCache = CampCache::singleton();
        $CampCache->clear('user');

        return true;
    } // fn create

    /**
     * Delete record from database.
     *
     * @return boolean
     */
    function delete()
    {
        // Delete record from the database
        $deleted = parent::delete();

        /*
        if ($deleted) {
            if (function_exists("camp_load_translation_strings")) {
                camp_load_translation_strings("api");
            }
            $logtext = getGS('Article #$1: "$2" ($3) deleted.',
                $this->m_data['Number'], $this->m_data['Name'],    $this->getLanguageName())
                ." (".getGS("Publication")." ".$this->m_data['IdPublication'].", "
                ." ".getGS("Issue")." ".$this->m_data['NrIssue'].", "
                ." ".getGS("Section")." ".$this->m_data['NrSection'].")";
            Log::Message($logtext, null, 32);
        }
        */

        $CampCache = CampCache::singleton();
        $CampCache->clear('user');

        return $deleted;
    } // fn delete

    /**
     * Called when debate is deleted
     *
     * @param int $p_debate_nr
     */
    public static function OnDebateDelete($p_debate_nr)
    {
        if (count(Debate::getTranslations($p_debate_nr)) > 1) {
            return;
        }

        foreach (DebateArticle::getAssignments($p_debate_nr) as $record) {
            $record->delete();
        }
    }

    /**
     * Call this if an publication is deleted
     *
     * @param int $p_publication_id
     */
    public static function OnArticleDelete($p_article_language_id, $p_article_nr)
    {
        foreach (DebateArticle::getAssignments(null, $p_article_language_id, $p_article_nr) as $record) {
            $record->delete();
        }
    }

    /**
     * Get array of relations between publication and debate
     * You have to set param $p_publication_id,
     * or booth $p_debate_nr and $p_debate_language_id
     *
     * @param int $p_publication_id
     * @param int $p_debate_nr
     * @param int $p_debate_language_id
     * @return array(object DebateArticle, object DebateArticle, ...)
     */
    public static function getAssignments(  $p_debate_nr = null,
                                            $p_article_language_id, $p_article_nr,
                                            $p_offset = 0, $p_limit = 10, $p_orderStr = null)
    {
        global $g_ado_db;
        $records = array();

        $DebateArticle = new DebateArticle();
        $where = '';
        if (!empty($p_debate_nr)) {
            $where .= "AND fk_debate_nr = $p_debate_nr ";
        }
        if (!empty($p_article_language_id)) {
            $where .= "AND fk_article_language_id = $p_article_language_id ";
        }
        if (!empty($p_article_nr)) {
            $where .= "AND fk_article_nr = $p_article_nr ";
        }

        if (empty($where)) {
            return array();
        }

        $query = "SELECT    *
                  FROM      {$DebateArticle->m_dbTableName}
                  WHERE     1 $where
                  ORDER BY  fk_debate_nr DESC";
        $res = $g_ado_db->selectLimit($query, $p_limit == 0 ? -1 : $p_limit, $p_offset);

        while ($row = $res->fetchRow()) {
            $records[] = new DebateArticle($row['fk_debate_nr'], $row['fk_article_language_id'], $row['fk_article_nr']);
        }

        return $records;
    }

    /**
     * Get the responding publication object of an record
     *
     * @return object
     */
    public function getPublication()
    {
        $publication = new Publication($this->m_data['fk_publication_id']);

        return $publication;
    }

    /**
     * Get the PublicationId
     *
     * @return int
     */
    public function getPublicationId()
    {
        return $this->m_data['fk_publication_id'];
    }

    /**
     * Get the responding debate object for an record
     *
     * @param $p_language_id wanted language
     * @return object
     */
    public function getDebate($p_language_id)
    {
        $debate = new Debate($p_language_id, $this->m_data['fk_debate_nr']);

        return $debate;
    }

    /**
     * Get the debate number
     *
     * @return int
     */
    public function getDebateNumber()
    {
        return $this->m_data['fk_debate_nr'];
    }

} // class DebateArticle

?>
