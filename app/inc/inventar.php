<?php
/**
 *
 */
class InventarVerwaltung extends DB
{

  function __construct()  {
    parent::__construct();
  }

  public function createItem($name, $consumable, $dateStr, $info = "", $stock = 1, $ppe = 0, $tid = 0, $cat = 0){
    $sql = $this->mysqli->prepare("INSERT INTO inventar (stock, name, info, cat, consumable, angeschafft, ppe, transaktion) VALUES (?,?,?,?,?,?,?,?)");
    $sql->bind_param("issiisdi",
                    $stock,
                    $name,
                    $info,
                    $cat,
                    $consumable,
                    $dateStr,
                    $ppe,
                    $tid);
    $sql->execute();
    return $this->mysqli->insert_id == 0 ? false : $this->mysqli->insert_id;

  }

  public function getItemData($inr){
    $sql = $this->mysqli->prepare("SELECT LPAD(inr, 4, '0') 'inr', stock, name, info, cat, consumable, angeschafft, ppe, transaktion FROM inventar WHERE inr = ?");
    $sql->bind_param("i", $inr);
    $sql->execute();

    $result = $sql->get_result();
    $result = $result->fetch_all(MYSQLI_ASSOC);

    return $result[0];
  }
  public function addItemNote($inr, $note){
    return true;
  }

  public function print(){
    $page = isset($_GET["page"]) ? $_GET["page"] : 1;
    $perpage = isset($_GET["itemsperpage"]) ? $_GET["itemsperpage"] : 20;

    $offset = $perpage * ($page - 1);
    $sql = $this->mysqli->prepare("SELECT
      LPAD(inr, 4, '0') 'inr',
      cat 'kategorieId',
      name 'bezeichnung',
      info,
      consumable,
      stock,
      angeschafft
      FROM inventar ORDER BY inr LIMIT ?,?");
    $sql->bind_param("ii", $offset, $perpage);
    $sql->execute();

    $result = $sql->get_result();
    $result = $result->fetch_all(MYSQLI_ASSOC);

    //var_dump($result);
    ?>
    <div class="inv-list">
        <?php
        foreach ($result as $item) {
          $inr = isset($item["inr"]) ? $item["inr"] : "";
          ?>

          <a href="/inventar/view?inr=<?php echo $inr; ?>">
            <div class="inv-item">
              <div class="category-section">
                <p><span class="icon" data-catid="<?php echo isset($item["kategorieId"]) ? $item["kategorieId"] : ""; ?>">category</span></p>
                <p class="inr"><?php echo $inr; ?></p>

              </div>
              <div class="main-section">
                <p class="bezeichnung"><?php echo isset($item["bezeichnung"]) ? $item["bezeichnung"] : ""; ?></p>
                <p class="info"><?php echo isset($item["info"]) ? $item["info"] : ""; ?></p>
              </div>
              <div class="bonus-info-section">
                <?php if($item["consumable"] == true){ ?>
                  <div class="stock">
                    <p class="stockcount">
                      <span class="icon">inventory_2</span>
                      <span class="cnt"><?php echo($item["stock"]); ?></span>
                    </p>

                  </div>
                <?php }
                if(false){ // Wenn Gegenstand gerade in Benutzung

                }
                ?>
              </div>
            </div>
          </a>

        <?php }
        ?>
    </div>
    <?
  }
}


 ?>
