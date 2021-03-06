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
      $db = Database::GetInstance();

      $create_query = 'CREATE TABLE `'.DB_PREFIX.'tags` (';
      $create_query .= '  `tag_id` bigint(20) NOT NULL AUTO_INCREMENT,';
      $create_query .= '  `name` text NOT NULL,';
      $create_query .= '  PRIMARY KEY (`tag_id`)';
      $create_query .= ') DEFAULT CHARSET=utf8';

      $db->Query($create_query);

      $create_query = 'CREATE TABLE `'.DB_PREFIX.'video_tags` (';
      $create_query .= '  `video_tag_id` bigint(20) NOT NULL AUTO_INCREMENT,';
      $create_query .= '  `video_id` bigint(20) NOT NULL,';
      $create_query .= '  `tag_id` bigint(20) NOT NULL,';
      $create_query .= '  PRIMARY KEY (`video_tag_id`)';
      $create_query .= ') DEFAULT CHARSET=utf8';

      $db->Query($create_query);
    }

    static function Uninstall() {
      $db = Database::GetInstance();

      $drop_query = 'DROP TABLE IF EXISTS `'.DB_PREFIX.'tags`';

      $db->Query($drop_query);

      $drop_query = 'DROP TABLE IF EXISTS `'.DB_PREFIX.'video_tags`';

      $db->Query($drop_query);
    }

    static function Settings() {
      global $config;

      App::LoadClass ('Video');
      $db = Database::GetInstance();

      if (isset ($_POST['easycategory_action'])) {

        if($_POST['easycategory_action'] == "add_tag") {

          $tag_query = "SELECT * FROM " . DB_PREFIX . "tags WHERE `name`='" . mysql_real_escape_string($_POST['easycategory_tag_name'])."'";

          $tag_result = $db->Query ($tag_query);
          $tag_count = $db->Count ($tag_result);

          if($tag_count == 0) {
            $insert_query = "INSERT INTO " . DB_PREFIX . "tags (`name`) VALUES ('" . mysql_real_escape_string($_POST['easycategory_tag_name']) . "')";
            $db->Query ($insert_query);
          }

        } else if ($_POST['easycategory_action'] == "delete_tag") {

          $tag_query = "SELECT * FROM " . DB_PREFIX . "tags WHERE `tag_id` = " . $_POST['easycategory_tag_id'];

          $tag_result = $db->Query ($tag_query);
          $tag_count = $db->Count ($tag_result);

          while ($row = $db->FetchObj ($tag_result)) {
            $delete_query = "DELETE FROM " . DB_PREFIX . "tags WHERE `tag_id`=".$row->tag_id;
            $db->Query ($delete_query);
            $delete_query = "DELETE FROM " . DB_PREFIX . "video_tags WHERE `tag_id`=".$row->tag_id;
            $db->Query ($delete_query);
          }

          if($tag_count == 1) {
          }

        } else if ($_POST['easycategory_action'] == "update") {

          $video_query = "SELECT * FROM " . DB_PREFIX . "videos WHERE `video_id` = " . $_POST['easycategory_video_id'];

          $video_result = $db->Query ($video_query);
          $video_count = $db->Count ($video_result);

          if($video_count == 1) {
            $update_query = "UPDATE " . DB_PREFIX . "videos SET ";
            $update_query .= "`title` = '" . mysql_real_escape_string($_POST['easycategory_title']) . "', ";
            $update_query .= "`cat_id` = " . $_POST['easycategory_cat_id'] . " ";
            $update_query .= "WHERE `video_id` = " . $_POST['easycategory_video_id'];
            $db->Query ($update_query);

            $delete_query="DELETE FROM " . DB_PREFIX . "video_tags WHERE `video_id`= " . $_POST['easycategory_video_id'];
            $db->Query ($delete_query);

            if($_POST['easycategory_tag_list'] != "" && sizeof($_POST['easycategory_tag_list']) > 0) {
              $taglist = explode(',', $_POST['easycategory_tag_list']);

              if(sizeof($taglist) > 0) {
                $values = array();
                foreach($taglist as $tag_id) {
                  array_push($values, "(".$_POST['easycategory_video_id'].", ".$tag_id.")");
                }
                $insert_query="INSERT INTO " . DB_PREFIX . "video_tags (`video_id`, `tag_id`) VALUES " . implode(',', $values);
                $db->Query ($insert_query);
              }
            }
          }

        }

      } else {

      $videos_query = "SELECT video_id FROM " . DB_PREFIX . "videos WHERE status = 'approved'";

      if(isset($_GET['cat_filter'])) {
        $videos_query .= " AND cat_id=".mysql_real_escape_string($_GET['cat_filter']);
      }

      $videos_query .= " ORDER BY video_id DESC";
      $videos_result = $db->Query ($videos_query);
      $videos_total = $db->Count ($videos_result);

      $tags_query = "SELECT * FROM " . DB_PREFIX . "tags";
      $tags_result = $db->Query ($tags_query);
      $tags_total = $db->Count ($tags_result);

      $tags = array();
      while ($row = $db->FetchObj ($tags_result)):
        array_push($tags, $row);
      endwhile;

      $categories = array();
      // Retrieve Category names
      $cat_query = "SELECT cat_id, cat_name FROM " . DB_PREFIX . "categories";
      $cat_result = $db->Query ($cat_query);
      while ($row = $db->FetchObj ($cat_result)) {
          $categories[$row->cat_id] = $row->cat_name;
      }

?>

<h1>EasyCategory</h1>

<script type="text/javascript" src="<?=$config->theme_url?>/js/jquery.min.js"></script>
<script>
$(document).ready(function(){

  $('form.add_tag').submit( function(e) {
    e.preventDefault();
    var tag_name=$(this).find('input.easycategory_tag_name').val();
    $.ajax(window.location.href, {
      type: "POST",
      data: {
        easycategory_action:"add_tag",
        easycategory_tag_name:tag_name
      },
      success: function( response ) {
        window.location.reload()
      }
    });
    return false;
  });

  $('a.remove_tag').click(function() {
    var item = $(this).parents('li');
    $.ajax(window.location.href, {
      type: "POST",
      data: {
        easycategory_action:"delete_tag",
        easycategory_tag_id: $(this).data('tagid')
      },
      success: function( response ) {
        window.location.reload()
      }
    });
    return false;
  });

  $('.video .update').click( function(e) {
    e.preventDefault();
    var spinner = $(this).find('.spinner'),
        video = $(this).parents('.video');
    spinner.show();
    $.ajax(window.location.href, {
      type: "POST",
      data: {
        easycategory_action:"update",
        easycategory_video_id:video.find('.easycategory_video_id').val(),
        easycategory_title: video.find('.easycategory_title').val(),
        easycategory_cat_id: video.find('.easycategory_cat_id:checked').val(),
        easycategory_tag_list: video.find('.video-tags input:checked').map(function(){ return $(this).val(); }).toArray().join(',')
      },
      success: function( response ) {
        spinner.hide();
      }
    });
    return false;
  });

});
</script>

<h2>Tag list</h2>

<div class="block">
  <ul id="tag_list">
  <?php foreach ($tags as $index => $tag): ?>
  <li><?=$tag->name?> (<a href="#" class="remove_tag" data-tagid="<?=$tag->tag_id?>">remove</a>)</li>
  <?php endforeach; ?>
  </ul>
  <form method="post" class="add_tag">
    <input type="hidden" name="easycategory_action" value="add_tag"/>
    <input type="text" class="easycategory_tag_name" name="easycategory_tag_name"/>
    <input type="submit" value="Add Tag"/>
  </form>
</div>

<div class="block">
  <p>Category filter : <br>

  <?php if(!isset($_GET['cat_filter'])) { ?>
  <b><a href='/cc-admin/plugins_settings.php?plugin=EasyCategory'>All</a></b> |
  <?php } else { ?>
  <a href='/cc-admin/plugins_settings.php?plugin=EasyCategory'>All</a> |
  <?php } ?>

  <?php foreach ($categories as $cat_id => $cat_name): ?>
  <?php if(intval($cat_id) == intval($_GET['cat_filter'])) { ?>
  <b><a href='/cc-admin/plugins_settings.php?plugin=EasyCategory&cat_filter=<?=$cat_id?>'><?=$cat_name?></a></b> |
  <?php } else { ?>
  <a href='/cc-admin/plugins_settings.php?plugin=EasyCategory&cat_filter=<?=$cat_id?>'><?=$cat_name?></a> |
  <?php } ?>
  <?php endforeach; ?>
</div>

<?php if ($videos_total > 0): ?>
<div class="block list">
  <table>
    <thead>
        <tr>
            <td class="video-title large">Title</td>
            <td class="video-category large">Category</td>
        </tr>
    </thead>
    <tbody>
    <?php while ($row = $db->FetchObj ($videos_result)): ?>

      <?php
        $odd = empty ($odd) ? true : false;
        $video = new Video ($row->video_id);

        $tags_query = "SELECT tag_id FROM " . DB_PREFIX . "video_tags WHERE `video_id`=".$row->video_id;
        $tags_result = $db->Query ($tags_query);

        $videotags = array();
        while ($rowtag = $db->FetchObj ($tags_result)):
          array_push($videotags, $rowtag->tag_id);
        endwhile;
      ?>

      <tr class="video" class="<?=$odd ? 'odd' : ''?>">
        <form>
        <input type="hidden" name="easycategory_action" value="update"/>
        <input type="hidden" class="easycategory_video_id" name="easycategory_video_id" value="<?= $video->video_id ?>"/>
        <td class="video-title">
          <input type="text" class="easycategory_title" name="easycategory_title" value="<?=$video->title?>"><br>
          <img src="<?=$config->thumb_url?>/<?=$video->filename?>.jpg" width="200px"/>
        </td>
        <td class="video-category">
          <?php foreach ($categories as $cat_id => $cat_name): ?>
          <input type="radio" class="easycategory_cat_id" name="easycategory_cat_id" value="<?=$cat_id?>" <?= ($video->cat_id == $cat_id) ? 'checked' : ''?>> <?=$cat_name?><br>
          <?php endforeach; ?>
          <p><input class="update" value="Update" type="submit"/><img class='spinner' src="/cc-content/plugins/EasyCategory/spinner.gif" style="display:none"></p>
        </td>
        <td class="video-tags">
          <?php foreach ($tags as $index => $tag): ?>
          <input type="checkbox" value="<?=$tag->tag_id?>" <?= in_array($tag->tag_id, $videotags) ? 'checked' : ''?>> <?=$tag->name?><br>
          <?php endforeach; ?>
        </td>
        </form>
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
}
?>
