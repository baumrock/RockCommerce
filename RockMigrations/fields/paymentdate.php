<?php

namespace ProcessWire;

return [
  'type' => 'datetime',
  'label' => 'Payment-Date',
  'dateOutputFormat' => 'd.m.Y',
  'inputType' => 'text',
  'dateSelectFormat' => 'yMd',
  'datepicker' => InputfieldDatetime::datepickerFocus,
  'dateInputFormat' => 'j.n.y',
  'timeInputFormat' => '',
  'defaultToday' => 0,
  'icon' => 'calendar',
];
