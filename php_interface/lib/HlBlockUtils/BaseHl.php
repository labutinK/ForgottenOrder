<?
namespace Local\HlBlockUtils;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserField\Types\EnumType;
use Bitrix\Main\UserFieldTable;

abstract class BaseHl
{
    public $tableName;
    public $entity;
    public $entityName;
    public $hlId;

    public function __construct()
    {
        $this->getEntity();
    }

    private function getHlId()
    {
        \CModule::IncludeModule('highloadblock');

        $result = HighloadBlockTable::getList([
            'filter' => [
                '=TABLE_NAME' => $this->tableName
            ]
        ])->fetch()['ID'];

        if (!isset($result)) {
            throw new \Exception('Highload блока не существует', 1);
        }

        return $result;
    }

    private function getEntity(): void
    {
        if ($this->hlId = $this->getHlId()) {
            $hlblock = HighloadBlockTable::getById($this->hlId)->fetch();
            $entity = HighloadBlockTable::compileEntity($hlblock);
            $this->entityName = $entity->getName();
            $this->entity = $entity->getDataClass();
        }
    }


    public function getById(int $id, bool $origin = false)
    {
        return $this->entity::getById($id)->fetch();
    }

    public function getByFilter(array $filter = [], bool $by_id = false, array $sort = array('ID' => 'DESC')): ?array
    {
        $result = [];
        $res = $this->entity::getList([
            'select' => ['*'],
            'order' => $sort,
            'filter' => $filter,
        ])->fetchAll() ?: [];

        if ($by_id) {
            foreach ($res as $item) {
                $result[$item['ID']] = $item;
            }
        } else {
            $result = $res;
        }
        return $result;
    }

    public function getAll(): ?array
    {
        return $this->entity::getList([
            'order' => ['ID' => 'desc']
        ])->fetchAll();
    }

    public function getAllbyId(): ?array
    {
        $result = [];

        $res = $this->entity::getList([
            'order' => ['ID' => 'ASC']
        ])->fetchAll();
        foreach ($res as $item) {
            $result[$item['ID']] = $item;
        }

        return $result;
    }


    public function prepareDates(array $fields): array
    {
        foreach ($fields as $key => &$el) {
            if (false === \strpos($key, 'DATE')) {
                continue;
            }

            $el = DateTime::createFromPhp(new \DateTime($el));
        }

        return $fields;
    }

    /**
     *
     * @return int|array
     */
    public function add(array $fields)
    {
        $fields = $this->prepareDates($fields);
        $result = $this->entity::add($fields);

        if ($result->isSuccess()) {
            return $result->getId();
        } else {
            throw new \Exception(implode(',\n', $result->getErrorMessages()), 1);
        }
    }

    public function update(int $id, array $fields)
    {
        $result = $this->entity::update($id, $fields);

        if (!$result->isSuccess()) {
            return $result->getErrorMessages();
        }

        return $result;
    }

    public function delete($id)
    {
        return $this->entity::Delete($id);
    }
}
