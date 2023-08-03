<?php

class BalfComment {

  private $connection = null;

  public function __construct($con){
    $this->connection = $con;
  }

  public function getComments($url){
    $query = "SELECT * FROM `BalfComment_Posts` WHERE comment_url = :url ORDER BY timestamp";
    $stmt = $this->connection->prepare($query);

    if(!$stmt->execute(array(":url" => $url))){
      return false;
    }

    $query = "SELECT * FROM `BalfComment_Replies` WHERE parent_comment = :parent ORDER BY timestamp";
    $reply_statement = $this->connection->prepare($query);

    //Iterate the comments
    $main_array = array();
    while($comment = $stmt->fetch(PDO::FETCH_ASSOC)){

      $comment['replies'] = array();


      $reply_statement->execute(array(":parent" => $comment['comment_id']));

      while($reply = $reply_statement->fetch(PDO::FETCH_ASSOC)){
        array_push($comment['replies'], $reply);
      }

      array_push($main_array, $comment);
    }

    return $main_array;

  }

  public function newComment($url, $title, $text, $poster_name){
    $timestamp = time();
    $query = "INSERT INTO `BalfComment_Posts` (comment_url, comment_title, comment_text, poster_name, timestamp) VALUES (:url, :title, :text, :poster, :timestamp)";
    $stmt = $this->connection->prepare($query);

    if(!$stmt->execute(array(":url" => $url, ":title" => htmlentities($title), ":text" => htmlentities($text), ":poster" => htmlentities($poster_name), ":timestamp" => $timestamp))){
      return false;
    }

    return true;

  }

  public function newReply($post_id, $text, $poster_name){
    $timestamp = time();

    $query = "INSERT INTO `BalfComment_Replies` (parent_comment, reply_text, reply_user, timestamp) VALUES (:parent, :text, :poster, :timestamp)";
    $stmt = $this->connection->prepare($query);

    if(!$stmt->execute(array(":parent" => $post_id, ":text" => htmlentities($text), ":poster" => htmlentities($poster_name), ":timestamp" => $timestamp))){
      return false;
    }

    return true;
  }

  public function generateReplyButton($comment, $text){
    return '<button class="bc_reply_button button" data-comment-id="'.$comment['comment_id'].'">'.$text.'</button>';
  }

  public function autoGenerateComments($url){
    $comments = $this->getComments($url);

    $output = '<div class="balfcomments">';
    if(count($comments) > 0){
      foreach($comments as $comment){
        $output .= '<div class="balfcomment">';
          $output .= '<div class="wrapper">';
            $output .= '<div class="row">';
              $output .= '<div class="col_lg_9 col_md_9 col_sm_8 col_xs_6 col_xxs_6">';
                $output .= '<div class="poster">'.$comment['poster_name'].':</div>';
              $output .= '</div>';
              $output .= '<div class="col_lg_3 col_md_3 col_sm_4 col_xs_6 col_xxs_6">';
                $output .= '<div class="timestamp">'.date("Y-m-d H:i:s", $comment['timestamp']).'</div>';
              $output .= '</div>';
            $output .= '</div>';
            $output .= '<div class="content">';
              $output .= '<div class="title">'.$comment['comment_title'].'</div>';
              $output .= '<div class="text">'.$comment['comment_text'].'</div>';
            $output .= '</div>';
          $output .= '</div>';
          $output .= '<div class="replies">';
          $last_poster = '';//$comment['poster_name'];
          $count = 0;
          $open = false;
          $print_name = false;

          foreach($comment['replies'] as $reply){
            $print_name = false;
            if($last_poster != $reply['reply_user']){
              $print_name = true;
              if($count > 0){
                $output .= '</div>';
                $open = false;
              }
              $output .= '<div class="comment_group">';
              $open = true;
            }
            $last_poster = $reply['reply_user'];


            $output .= '<div class="reply">';
              $output .= '<div class="wrapper">';
                if($print_name){
                  $output .= '<div class="poster">'.$reply['reply_user'].'</div>';
                }
                $output .= '<div class="text">'.$reply['reply_text'].'</div>';
              $output .= '</div>';
            $output .= '</div>';
            $count++;
          }
          if($open){
            $output .= '</div>';
          }
          $output .= '</div>';
          $output .= '<div class="reply_zone"><div class="right_align"><button class="bc_reply_button button" data-comment-id="'.$comment['comment_id'].'">Reply</button></div></div>';

        $output .= '</div>';
      }
    } else {
      $output .= '<p class="center_align">There are no comments on this page.</p>';
    }

    $output .= '</div>';

    return $output;
  }


}


?>
