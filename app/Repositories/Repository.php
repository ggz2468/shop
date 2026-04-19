<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use Exception;

abstract class Repository
{
    /**
     * Model Class 名稱
     * 
     * @var string
     */
    protected string $modelClassName;

    /**
     * 建構子
     * 
     * @return void
     * 
     * @throws \Exception
     */
    public function __construct()
    {
        $classReflector = new ReflectionClass($this);
        $repositoryClassShortName = $classReflector->getShortName();
        $modelClassShortName = strstr($repositoryClassShortName, 'Repository', true);

        if (!class_exists($modelClassFullName = "App\\Models\\$modelClassShortName")) {
            throw new Exception("Model class [$modelClassFullName] does not exist.");
        }

        $classReflector = new ReflectionClass($modelClassFullName);

        if (!$classReflector->isSubclassOf(Model::class)) {
            throw new Exception("Class [$modelClassFullName] is not a valid Eloquent model.");
        }

        $this->modelClassName = $modelClassFullName;
    }

    /**
     * 新增資料
     * 
     * @param array<string, mixed> $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $data)
    {
        return $this->modelClassName::create($data);
    }

    /**
     * 更新資料
     * 
     * @param array<int, array<int, mixed>> $conditions
     * @param array<string, mixed> $data
     * @return int
     */
    public function update(array $conditions, array $data)
    {
        return $this->filter($conditions)->update($data);
    }

    /**
     * 刪除資料
     * 
     * @param array<int, array<int, mixed>> $conditions
     * @return int
     */
    public function delete(array $conditions)
    {
        return $this->filter($conditions)->delete();
    }

    /**
     * 取得條件篩選後的資料
     * 
     * @param array<int, array<int, mixed>> $conditions
     * @param array<int, string> $relations
     * @param array<int, string>|array<int, array<int, string>> $orderBy
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get(array $conditions, array $relations = [], array $orderBy = [], int $limit = 100)
    {
        $query = $this->filter($conditions)->with($relations);

        foreach ($this->normalizeOrderBy($orderBy) as $order) {
            $query = $query->orderBy(...$order);
        }
        
        return $query->limit($limit)->get();
    }

    /**
     * 取得經條件篩選後，指定頁碼內的資料
     * 
     * @param array<int, array<int, mixed>> $conditions
     * @param array<int, string> $relations
     * @param array<int, string>|array<int, array<int, string>> $orderBy
     * @param int $rowCountsPerPage
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(array $conditions, array $relations = [], array $orderBy = [], int $rowCountsPerPage = 10, int $page = 1)
    {
        $query = $this->filter($conditions)->with($relations);

        foreach ($this->normalizeOrderBy($orderBy) as $order) {
            $query = $query->orderBy(...$order);
        }

        return $query->paginate($rowCountsPerPage, ['*'], 'page', $page);
    }

    /**
     * 取得條件篩選後的第一筆資料
     * 
     * @param array<int, array<int, mixed>> $conditions
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function first(array $conditions)
    {
        return $this->filter($conditions)->first();
    }

    /**
     * 取得條件篩選後的資料筆數
     * 
     * @param array<int, array<int, mixed>> $conditions
     * @return int
     */
    public function count(array $conditions)
    {
        return $this->filter($conditions)->count();
    }

    /**
     * 取得條件篩選後的資料是否存在
     * 
     * @param array<int, array<int, mixed>> $conditions
     * @return bool
     */
    public function exists(array $conditions)
    {
        return $this->filter($conditions)->exists();
    }

    /**
     * 取得條件篩選後的資料是否不存在
     * 
     * @param array<int, array<int, mixed>> $conditions
     * @return bool
     */
    public function doesNotExist(array $conditions)
    {
        return $this->filter($conditions)->doesntExist();
    }

    /**
     * 取得所有資料
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all()
    {
        return $this->modelClassName::all();
    }

    /**
     * 資料條件篩選
     * 
     * @param array<int, array<int, mixed>> $conditions
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function filter(array $conditions)
    {
        $query = $this->modelClassName::query();

        foreach ($conditions as $condition) {
            $query = $query->where(...$condition);
        }

        return $query;
    }

    /**
     * 正規化排序條件格式
     *
     * 支援以下格式：
     * - ['id', 'asc']
     * - [['view_counts', 'desc'], ['id', 'asc']]
     *
     * @param array<int, string>|array<int, array<int, string>> $orderBy
     * @return array<int, array{0: string, 1: string}>
     */
    private function normalizeOrderBy(array $orderBy): array
    {
        if ($orderBy === []) {
            return [];
        }

        if (isset($orderBy[0]) && is_string($orderBy[0])) {
            $column = (string) ($orderBy[0] ?? 'id');
            $direction = strtolower((string) ($orderBy[1] ?? 'asc'));

            return [[$column, $direction === 'desc' ? 'desc' : 'asc']];
        }

        $normalized = [];

        foreach ($orderBy as $order) {
            if (!is_array($order) || !isset($order[0]) || !is_string($order[0])) {
                continue;
            }

            $column = $order[0];
            $direction = strtolower((string) ($order[1] ?? 'asc'));
            $normalized[] = [$column, $direction === 'desc' ? 'desc' : 'asc'];
        }

        return $normalized;
    }
}
