<?php

namespace App\Repository\Modules\Notes;

use App\Entity\Modules\Notes\MyNotes;
use App\Entity\Modules\Notes\MyNotesCategories;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MyNotesCategories|null find($id, $lockMode = null, $lockVersion = null)
 * @method MyNotesCategories|null findOneBy(array $criteria, array $orderBy = null)
 * @method MyNotesCategories[]    findAll()
 * @method MyNotesCategories[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MyNotesCategoriesRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, MyNotesCategories::class);
    }

    /**
     * @return Statement
     * @throws Exception
     */
    public function buildHaveCategoriesNotesStatement(): Statement
    {
        $connection = $this->_em->getConnection();

        $sql = "
            SELECT COUNT(id)
            FROM my_note
            
            WHERE 1
            AND category_id IN(?)
        ";

        $stmt = $connection->prepare($sql);

        return $stmt;
    }

    /**
     * @param Statement $statement
     * @param array $categories_ids
     * @return bool
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function executeHaveCategoriesNotesStatement(Statement $statement, array $categories_ids): bool
    {
        $ids = "'" . implode("','", $categories_ids) . "'";

        $statement->execute([$ids]);
        $result = $statement->fetchFirstColumn();

        if( empty($result) ){
            return false;
        }

        return true;
    }

    /**
     * @param array $categories_ids
     * @return MyNotesCategories[]
     */
    public function getChildrenCategoriesForCategoriesIds(array $categories_ids): array
    {
        $query_builder = $this->_em->createQueryBuilder();

        $query_builder->select("mnc_child")
            ->from(MyNotesCategories::class, "mnc")
            ->join(MyNotesCategories::class, "mnc_child", Join::WITH, "mnc_child.parent_id = mnc.id")
            ->where("mnc.id IN (:categoriesIds)")
            ->andWhere("mnc_child.deleted = 0")
            ->setParameter("categoriesIds", $categories_ids);

        $query   = $query_builder->getQuery();
        $results = $query->execute();

        return $results;
    }

    /**
     * @param array $categories_ids
     * @return string[]
     */
    public function getChildrenCategoriesIdsForCategoriesIds(array $categories_ids): array
    {
        $query_builder = $this->_em->createQueryBuilder();

        $query_builder->select("mnc_child.id")
            ->from(MyNotesCategories::class, "mnc")
            ->join(MyNotesCategories::class, "mnc_child", Join::WITH, "mnc_child.parent_id = mnc.id")
            ->where("mnc.id IN (:categoriesIds)")
            ->andWhere("mnc_child.deleted = 0")
            ->setParameter("categoriesIds", $categories_ids);

        $query   = $query_builder->getQuery();
        $results = $query->execute();
        $ids     = array_column($results, 'id');

        return $ids;
    }

    /**
     * @return array
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function getCategories(): array
    {
        $connection = $this->_em->getConnection();

        $sql = "
            SELECT
                mnc.name               AS category,
                mnc.icon               AS icon,
                mnc.color              AS color,
                mnc.id                 AS category_id,
                mnc.parent_id          AS parent_id,
                childrens.childrens_id AS childrens_id
            FROM my_note mn
                
            JOIN my_note_category mnc
            ON mnc.id = mn.category_id
                
            LEFT JOIN (
                SELECT 
                    GROUP_CONCAT(DISTINCT mnc_.id)  AS childrens_id,
                    mnc_.parent_id                  AS category_id
                
                FROM my_note_category mnc_
                
                GROUP BY mnc_.parent_id
            ) AS childrens
            ON childrens.category_id = mnc.id
            
            WHERE mn.deleted = 0
            AND mnc.deleted  = 0
            
            GROUP BY mnc.name
        ";

        $statement = $connection->prepare($sql);
        $statement->execute();
        $results = $statement->fetchAll();

        return (!empty($results) ? $results : []);
    }

    /**
     * @return MyNotesCategories[]
     */
    public function findAllNotDeleted(): array
    {
        $entities = $this->findBy([MyNotesCategories::KEY_DELETED => 0]);
        return $entities;
    }

    /**
     * Returns categories inside given parentId
     * @param string $name
     * @param string $category_id
     * @return MyNotesCategories[]
     */
    public function getNotDeletedCategoriesForParentIdAndName(string $name, ?string $category_id): array
    {
        $query_builder = $this->_em->createQueryBuilder();
        $query_builder->select("mnc")
            ->from(MyNotesCategories::class, "mnc")
            ->where("mnc.deleted = 0");

        if( is_null($category_id) ){
            $query_builder
                ->andWhere("mnc.name = :name")
                ->andWhere("mnc.parent_id IS NULL")
                ->setParameters([
                    "name" => $name,
                ]);
        }else{
            $query_builder
                ->andWhere("mnc.name      = :name")
                ->andWhere("mnc.parent_id = :categoryId")
                ->setParameters([
                    "name"       => $name,
                    "categoryId" => $category_id,
                ]);
        }

        $query   = $query_builder->getQuery();
        $results = $query->execute();

        return $results;
    }

}
