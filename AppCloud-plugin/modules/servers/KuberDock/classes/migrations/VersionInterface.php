<?php

namespace migrations;

interface VersionInterface
{
    public function up();
    public function down();
}