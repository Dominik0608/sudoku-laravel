<?php

/*
  Create by: https://www.hackandphp.com/blog/simple-sudoku-web-application-using-php
*/

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class SudokuController extends Controller
{
  public $_matrix;

  public $sudoku=[
    [7, 5, 1,  8, 4, 3,  9, 2, 6],
    [8, 9, 3,  6, 2, 5,  1, 7, 4], 
    [6, 4, 2,  1, 7, 9,  5, 8, 3],
    [4, 2, 5,  3, 1, 6,  7, 9, 8],
    [1, 7, 6,  9, 8, 2,  3, 4, 5],
    [9, 3, 8,  7, 5, 4,  6, 1, 2],
    [3, 6, 4,  2, 9, 7,  8, 5, 1],
    [2, 8, 9,  5, 3, 1,  4, 6, 7],
    [5, 1, 7,  4, 6, 8,  2, 3, 9]];
 
  public function __construct(array $matrix = null) {
    if (!isset($matrix)) {
      $this->_matrix = $this->_getEmptyMatrix();
    } else {
      $this->_matrix = $matrix;
    }
  }
 
  /*
    Sudoku generátor - visszatérési értéke egy kitöltetlen sudoku mátrix
  */
  public function generate() {
    $this->_matrix = $this->_solve($this->_getEmptyMatrix());
    $cells = array_rand(range(0, 80), 30);
    $i = 0;
    foreach ($this->_matrix as &$row) {
      foreach ($row as &$cell) {
        if (!in_array($i++, $cells)) {
          $cell = null;
        }
      }
    }
    
    return response()->json($this->_matrix);
  }

  /*
    Sudoku ellenőrző - helyes megoldás esetén true-val tér vissza, ellenben false
  */
  public function checker(Request $request) {
    if (!$request) { return response()->json(false); }
    if (empty($request->sudoku)) { return response()->json(false); }
    if (empty($request->username)) { return response()->json(false); }

    $matrix = json_decode($request->sudoku);

    for ($i = 0; $i < 9; $i++) { 
      for ($j = 0; $j < 9; $j++) { 
        if (count($this->_getPermissible($matrix, $i, $j)) > 0) {
          return response()->json(false);
        }
      }
    }

    if (count(User::where('name', $request->username)->get()) == 0) { 
      $user = new User();
      $user->name = $request->username;
      $user->save();
    }
    
    return response()->json(true);
  }
 
  private function _getEmptyMatrix() {
    return array_fill(0, 9, array_fill(0, 9, 0));
  }
 
  private function _solve($matrix) {
    while(true) {
      $options = array();
      foreach ($matrix as $rowIndex => $row) {
        foreach ($row as $columnIndex => $cell) {
          if (!empty($cell)) {
            continue;
          }
          $permissible = $this->_getPermissible($matrix, $rowIndex, $columnIndex);
          if (count($permissible) == 0) {
            return false;
          }
          $options[] = array(
            'rowIndex' => $rowIndex,
            'columnIndex' => $columnIndex,
            'permissible' => $permissible
          );
        }
      }
      if (count($options) == 0) {
        return $matrix;
      }
 
      usort($options, array($this, '_sortOptions'));
 
      if (count($options[0]['permissible']) == 1) {
        $matrix[$options[0]['rowIndex']][$options[0]['columnIndex']] = current($options[0]['permissible']);
        continue;
      }
 
      foreach ($options[0]['permissible'] as $value) {
        $tmp = $matrix;
        $tmp[$options[0]['rowIndex']][$options[0]['columnIndex']] = $value;
        if ($result = $this->_solve($tmp)) {
          return $result;
        }
      }
 
      return false;
    }
  }
 
  private function _getPermissible($matrix, $rowIndex, $columnIndex) {
    $valid = range(1, 9);
    $invalid = $matrix[$rowIndex];
    for ($i = 0; $i < 9; $i++) {
      $invalid[] = $matrix[$i][$columnIndex];
    }
    $box_row = $rowIndex % 3 == 0 ? $rowIndex : $rowIndex - $rowIndex % 3;
    $box_col = $columnIndex % 3 == 0 ? $columnIndex : $columnIndex - $columnIndex % 3;
    $invalid = array_unique(array_merge(
      $invalid,
      array_slice($matrix[$box_row], $box_col, 3),
      array_slice($matrix[$box_row + 1], $box_col, 3),
      array_slice($matrix[$box_row + 2], $box_col, 3)
    ));
    $valid = array_diff($valid, $invalid);
    shuffle($valid);
    return $valid;
  }
 
  private function _sortOptions($a, $b) {
    $a = count($a['permissible']);
    $b = count($b['permissible']);
    if ($a == $b) {
      return 0;
    }
    return ($a < $b) ? -1 : 1;
  }
}
