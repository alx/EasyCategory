<?php

class EasyCategory {

    static function Load() {
    }

    static function Info() {
        return array (
            'name'    => 'EasyCategory',
            'author'  => 'Alexandre Girard',
            'version' => '1.0.0',
            'site'    => 'http://alexgirard.com/',
            'notes'   => 'Easily change categories of all your CumulusClips videos'
        );
    }

    static function Install() {
    }

    static function Uninstall() {
    }

    static function Settings() {
      global $config;

      App::LoadClass ('Video');
      $db = Database::GetInstance();

      if (isset ($_POST['easycategory_action']) &&  $_POST['easycategory_action'] == "update") {

        $query = "SELECT * FROM " . DB_PREFIX . "videos WHERE `video_id` = " . Database::Escape($_POST['easycategory_video_id']);
        $video_result = $db->Query ($query);
        $video_count = $db->Count ($video_result);

        if($video_count == 1) {
          $query = "UPDATE " . DB_PREFIX . "videos SET ";
          $query .= "`cat_id` = " . Database::Escape($_POST['easycategory_cat_id']) . " ";
          $query .= "WHERE `video_id` = " . Database::Escape($_POST['easycategory_video_id']);
          $db->Query ($query);
        }

      }

      $query = "SELECT video_id FROM " . DB_PREFIX . "videos WHERE status = 'approved'";
      $query .= " ORDER BY video_id DESC";
      $result = $db->Query ($query);
      $total = $db->Count ($result);

      $categories = array();
      // Retrieve Category names
      $query = "SELECT cat_id, cat_name FROM " . DB_PREFIX . "categories";
      $result = $db->Query ($query);
      while ($row = $db->FetchObj ($result)) {
          $categories[$row->cat_id] = $row->cat_name;
      }

?>

<h1>EasyCategory</h1>

<?php if ($total > 0): ?>
<div class="block list">
  <table>
    <thead>
        <tr>
            <td class="video-title large">Title</td>
            <td class="video-category large">Category</td>
        </tr>
    </thead>
    <tbody>
    <?php while ($row = $db->FetchObj ($result)): ?>

      <?php
        $odd = empty ($odd) ? true : false;
        $video = new Video ($row->video_id);
      ?>

      <tr class="<?=$odd ? 'odd' : ''?>">
        <td class="video-title">
            <a href="<?=ADMIN?>/videos_edit.php?id=<?=$video->video_id?>" class="large"><?=$video->title?></a><br />
            <img src="<?=$config->thumb_url?>/<?=$video->filename?>.jpg" width="200px"/>
        </td>
        <td class="video-category">
          <form method="post">
            <input type="hidden" name="easycategory_action" value="update"/>
            <input type="hidden" name="easycategory_video_id" value="<?= $row->video_id ?>"/>
            <?php foreach ($categories as $cat_id => $cat_name): ?>
            <input type="radio" name="easycategory_cat_id" value="<?=$cat_id?>" <?= ($video->cat_id == $cat_id) ? 'checked' : ''?>> <?=$cat_name?><br>
            <?php endforeach; ?>
            <p><input value="Update" type="submit"/></p>
          </form>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
<div class="block"><strong>No videos found</strong></div>
<?php endif; ?>

<?php
    }
}
?>
