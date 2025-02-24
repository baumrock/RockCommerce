<?php

namespace ProcessWire;

return [
  'type' => 'datetime',
  'label' => 'Date-To',
  'dateOutputFormat' => 'd.m.Y H:i',
  'inputType' => 'text',
  'dateSelectFormat' => 'yMd',
  'datepicker' => InputfieldDatetime::datepickerFocus,
  'dateInputFormat' => 'j.n.y',
  'timeInputFormat' => 'H:i',
  'defaultToday' => 0,
];
