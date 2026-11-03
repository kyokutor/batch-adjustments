# 批次库存数量调整 API

[English](README.md) | 中文

这是一个独立的 Laravel 12 API 演示项目，支持预定义调整原因、库存调整历史记录以及事务内的库存更新。

## 环境要求

- PHP 8.2 或以上版本，以及 Laravel 所需的标准 PHP 扩展。
- Composer 2。
- PDO SQLite 扩展；使用 MySQL 或 PostgreSQL 时安装对应 PDO 扩展。
- 本项目的 API 不需要 Node.js。

## 安装与启动

在项目目录执行：

```sh
cd /Users/daniel/Desktop/test
composer run setup
php artisan serve
```

`setup` 会安装依赖、创建 `.env`、生成应用密钥、创建 SQLite 数据库文件，并执行数据库迁移和演示数据填充。

也可以手动执行以下步骤：

```sh
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

服务默认地址为 `http://localhost:8000`。Markdown 文件是说明文档，打开文档不会启动 API；启动服务需要执行上面的命令。

如果使用 MySQL 或 PostgreSQL，请在迁移之前修改 `.env` 中的 `DB_CONNECTION` 和数据库连接信息。存在并发写入时，建议使用 MySQL/InnoDB 或 PostgreSQL，以支持本项目使用的数据库行锁。SQLite 适合本地演示，但不支持 `SELECT FOR UPDATE`；并发写入时可能出现数据库繁忙或锁定错误。

## 演示数据

全新数据库中包含：

| 数据 | 示例内容 |
| --- | --- |
| 仓库 | Main Warehouse（主仓库）、Overflow Warehouse（备用仓库） |
| 产品 | Widget、Gadget |
| 批次 1 | `BATCH-001`，主仓库中的 Widget，初始数量 100 |
| 批次 2 | `BATCH-002`，备用仓库中的 Gadget，初始数量 50 |
| 原因 1–4 | 实物盘点修正、物品损坏、物品缺失、数据录入修正 |
| 额外原因 | 一个已停用的库存调整原因、一个有效但属于客户退货类型的原因，用于验证过滤和拒绝逻辑 |

接口示例中的 ID 以全新数据库为前提。重复执行 `php artisan db:seed` 会保留已有库存和记录，不会将已调整的库存重置为初始值。

## API 接口

成功响应的数据放在 `data` 字段中。请求请使用 JSON，并设置 `Accept: application/json`。

| 方法 | 路径 | 说明 |
| --- | --- | --- |
| GET | `/api/adjustment-reasons` | 返回启用且适用于库存调整的原因，状态码 200 |
| POST | `/api/inventory-adjustments` | 创建调整并更新库存，状态码 201 |
| GET | `/api/inventory-adjustments/{id}` | 查看调整及关联信息，状态码 200；不存在时返回 404 |

### 获取调整原因

```sh
curl -H 'Accept: application/json' http://localhost:8000/api/adjustment-reasons
```

### 创建库存调整

例如，系统数量为 100，实际盘点为 92：

```sh
curl -X POST http://localhost:8000/api/inventory-adjustments \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"batch_id":1,"reason_id":1,"new_quantity":92,"note":"A货架实物盘点"}'
```

| 参数 | 要求 |
| --- | --- |
| `batch_id` | 必填，必须是存在的批次 ID |
| `reason_id` | 必填，原因必须存在、已启用且类型为库存调整 |
| `new_quantity` | 必填，调整后的实际数量，必须是 0 至 2,147,483,647 的整数 |
| `note` | 可选，可为 null，最多 2,000 个字符 |

该示例将记录 `old_quantity: 100`、`new_quantity: 92` 和 `quantity_difference: -8`，并将批次库存更新为 92。

响应包含原因、批次、产品和仓库信息。之后查询时，`batch.quantity` 表示批次当前库存；调整记录中的旧数量和新数量仍保留当时的历史值。

输入无效时返回 HTTP 422，包含 Laravel 的 `message` 和按字段组织的 `errors`。原因不符合条件时，当前代码返回英文提示 `Choose an active inventory adjustment reason.`，含义为“请选择已启用的库存调整原因”。本次提供中文文档，接口提示和演示数据仍沿用英文。

不允许负数或小数库存。允许提交与当前数量相同的数量，此时仍创建调整记录，差值为 0。

### 查看调整记录

```sh
curl -H 'Accept: application/json' http://localhost:8000/api/inventory-adjustments/1
```

## 设计说明

- **模型关系：** 仓库和产品各自拥有多个批次；批次拥有多条调整记录；每条调整记录关联一个预定义原因。外键限制删除被引用的数据。
- **职责划分：** Form Request 负责校验，Controller 协调业务操作，API Resource 定义响应字段。业务规模较小，没有额外增加服务层或仓储层。
- **事务安全：** 在事务中先锁定批次并读取旧数量，再锁定原因并重新检查其有效性，然后创建调整记录和更新库存。任意一步失败，两项写入都会回滚。事务最多尝试执行三次以处理死锁。参考 [Laravel 数据库事务文档](https://laravel.com/docs/12.x/database#database-transactions)。
- **数量语义：** 客户端提交盘点后的绝对数量。连续提交时，每次都使用当时最新的库存作为旧数量。当前没有过期盘点冲突检测或幂等键，重复提交会生成独立记录。
- **原因历史：** 保存原因 ID，查询时读取关联原因的名称，没有复制原因名称作为历史快照。不提供原因编辑接口，也不提供调整记录的修改或删除接口。
- **演示范围：** 本地独立演示未实现身份认证。用于真实仓库前，应补充权限控制和操作人员记录。

## 测试与代码风格检查

```sh
composer test
vendor/bin/pint --test
```

功能测试使用隔离的内存 SQLite 数据库，覆盖：

- 原因过滤、调整历史字段、库存更新及关联数据查询。
- 无效批次、无效原因、停用原因、错误原因类型及无效参数。
- 库存归零、增加、数量不变和记录不存在的情况。
- 重复填充演示数据，以及发生异常时的事务回滚。

SQLite 测试不能证明并发行锁行为；应在实际使用的 MySQL 或 PostgreSQL 环境验证并发场景。

编写本项目时，当前环境未安装 PHP 和 Composer，因此尚未执行测试和格式检查。也尚未生成依赖锁文件；准备共享仓库时，请运行 Composer，并将生成的 `composer.lock` 提交到仓库。

## 发布到代码仓库

当前项目已初始化为本地 Git 仓库，尚未发布到远程仓库。可使用你有权限的远程仓库地址完成发布：

```sh
git add .
git commit -m "Add batch quantity adjustment API demo"
git branch -M main
git remote add origin <你的仓库地址>
git push -u origin main
```

请将 `<你的仓库地址>` 替换为实际地址。不要提交 `.env`、`vendor` 或 SQLite 数据库文件，现有 `.gitignore` 已排除这些内容。
