# phpstan-ci-sample

- [PHPStan](https://phpstan.org/)で静的解析をするサンプル
- [Deployer](https://deployer.org/)での自動デプロイを体験するサンプル

## 準備

### PHPStanのインストール

dev/phpstan/vendorにPHPStanをインストールします。

```shell
cd dev/phpstan
composer install
```

### Deployerのインストール

dev/deployer/vendorにDeployerをインストールします。

```shell
cd dev/deployer
composer install
```

## PHPStanの使い方

### ローカルで静的解析する

[phpstan.neon](phpstan.neon)のあるディレクトリで以下を実行します。

```shell
dev/phpstan/vendor/bin/phpstan analyze --memory-limit=1G
```

[sample.php](src/sample.php)に問題のあるコードを書いてから解析すれば、エラーとして出力されます。

### PHPStan導入以前のエラーを修復する

PHPStan導入以前のエラーは[phpstan-baseline.neon](phpstan-baseline.neon)に列挙されていて、解析時に無視される設定になっています。
そのようなエラーを解消してみます。

今回無視されている`Access to an undefined property OldClass::$prop.`は、
[old_code.php](src/old_code.php)にあるコメントアウト部分を元に戻すと解消されます。

ただしphpstan-baseline.neonに列挙されたエラーの場合、コードの修正だけでなくphpstan-baseline.neonに列挙されたエラーも消さなければ、
解析時にエラーが出てしまいます。
以下の部分を消すか、`dev/phpstan/vendor/bin/phpstan analyze --memory-limit=1G --generate-baseline --allow-empty-baseline`を
実行しエラーの整合性を取りましょう。

```yaml
# phpstan-baseline.neon(抜粋)
- message: "#^Access to an undefined property OldClass\\:\\:\\$prop\\.$#"
  count: 2
  path: src/old_code.php
```

解析結果が`[OK] No errors`となればOKです。

### CIで静的解析する

mainブランチに向けたプルリクエストを作成すると、
[.github/workflows/php.yml](.github/workflows/php.yml)により定義されたGitHub Workflowによってチェックが走ります。

## Deployerの使い方

### 前提

今回は簡単のためリモートサーバでなくローカルのディレクトリにデプロイを行います。
以下のディレクトリをサーバーだと思ってください。

- `/tmp/phpstan-ci-sample/staging/stg-01` → stagingサーバー1号機
- `/tmp/phpstan-ci-sample/staging/stg-02` → stagingサーバー2号機
- `/tmp/phpstan-ci-sample/production/pro-01` → productionサーバー1号機
- `/tmp/phpstan-ci-sample/production/pro-02` → productionサーバー2号機

### 最初のデプロイ

[deploy.yaml](deploy.yaml)のあるディレクトリで以下を実行するとstaging環境へデプロイします。

```shell
dev/deployer/vendor/bin/dep deploy --branch=main env=staging
```

以下のディレクトリが作成されていればOKです。

```shell
ls /tmp/phpstan-ci-sample/staging/*/phpstan-ci-sample/
```

今回、`env=staging`という[セレクタ](https://deployer.org/docs/7.x/selector)によってデプロイ先を指定しました。
これは、deploy.yaml内で`env: staging`というラベルが設定されているからです。

```yaml
# deploy.yaml(抜粋)
hosts:
  stg-01: # サーバー名
    local: true
    deploy_path: '/tmp/phpstan-ci-sample/staging/stg-01/phpstan-ci-sample'
    labels:
      env: staging # これにより env=staging セレクタの対象となる
  stg-02: # サーバー名
    local: true
    deploy_path: '/tmp/phpstan-ci-sample/staging/stg-02/phpstan-ci-sample'
    labels:
      env: staging # これにより env=staging セレクタの対象となる
```

### デプロイされたかどうかを確認する

リリース履歴を見るには`releases`タスクを実行します

```shell
dev/deployer/vendor/bin/dep releases env=staging
```

### ブランチを指定してデプロイ

特定のブランチ・タグ・リビジョンをデプロイすることもできます。

```shell
dev/deployer/vendor/bin/dep deploy --branch=wrong-code env=staging
dev/deployer/vendor/bin/dep deploy --tag=sample-tag env=staging
dev/deployer/vendor/bin/dep deploy --revision=26f66239067baa0096d9911999b8d8e964eb8301 env=staging
```

### ロールバック

ロールバックするには`rollback`タスクを実行します。

```shell
dev/deployer/vendor/bin/dep rollback env=staging
```

リリース履歴を確認します。

```shell
dev/deployer/vendor/bin/dep releases env=staging
```

ロールバックすると1つ前のリリースに切り替わります。
また、ロールバックしたリリースには`(bad)`マークが付き、次回以降のロールバック時にはスキップされるようになります。

```
+---------------------+-------------+------------ stg-01 -----+------------------------------------------+
| Date (UTC)          | Release     | Author     | Target     | Commit                                   |
+---------------------+-------------+------------+------------+------------------------------------------+
| 2022-09-18 10:02:43 | 1 (current) | naoki-ando | main       | 702975faa442ce496fff511f5c8f2a8255a7d0da |
| 2022-09-18 10:32:30 | 2 (bad)     | naoki-ando | wrong-code | 282a7eb8a74bd31e457b971ced46e3bfacaf5b81 |
+---------------------+-------------+------------+------------+------------------------------------------+
```

### (テクニック) パスワード認証SSH経由でのデプロイ

先ほどデプロイしたサーバー`stg-01`, `stg-02`は、ローカルなのでSSH接続について気にしなくてもデプロイができました。
しかし実際のサーバーへのデプロイにはSSHを使います。
そこで本節ではlocalhostへのSSHによるデプロイを通して、実サーバーへのデプロイの練習をします。
以下のようにSSH経由でのデプロイをするように設定したサーバー`pro-01`, `pro-02`を練習台とします。

```yaml
# deploy.yaml(抜粋)
hosts:
  pro-01:
    hostname: localhost # localhostへのSSH
    deploy_path: '/tmp/phpstan-ci-sample/production/pro-01/phpstan-ci-sample'
    labels:
      env: production
  pro-02:
    hostname: localhost # localhostへのSSH
    deploy_path: '/tmp/phpstan-ci-sample/production/pro-02/phpstan-ci-sample'
    labels:
      env: production
```

まず、普通にSSHできるか確認します。
Macの場合、「システム環境設定」→「共有」→「リモートログイン」にチェックを入れる必要があります。

```shell
ssh localhost
```

SSH接続にはパスワードを求められたと思いますが、Deployer実行時にパスワードを求められると高確率で失敗します。
そこでDeployerがパスワードなしでSSH接続するために、今回はssh_multiplexingを利用します。
(参考: [SSHの多重接続について](https://siguniang.wordpress.com/2013/11/30/notes-on-ssh-multiplexing/))
これは、一度確立したコネクションを使いまわすことで2度目以降のパスワード認証を回避しようというアイデアです。

SSH configに以下の設定を追加してください。

```
# ~/.ssh/config
ControlPath ~/.ssh/%r@%h:%p  # ソケットファイルが配置されるパス %r=リモートユーザ名, %h=ホスト名, %p=ポート番号
ControlMaster auto
ControlPersist 1m            # 使われないソケットは1分で破棄
```

設定後、SSH接続すると`~/.ssh/naoki-ando@localhost:22`のようなソケットファイルが作成されます。
この状態で再度SSH接続すると、パスワードなしで接続することができます。

では、実際にデプロイをしてみます。

```shell
# 接続を確立
ssh localhost

# SSH接続中、別のシェルで
dev/deployer/vendor/bin/dep deploy --branch=main env=production
```

以下のディレクトリが作成されていればOKです。

```shell
ls /tmp/phpstan-ci-sample/production/*/phpstan-ci-sample/
```
