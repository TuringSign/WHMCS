<?php

namespace ModulesGarden\TuringSign\Actions;

abstract class AbstractAction
{
    public function __construct(
        protected array $params = []
    ) {}

    public abstract function execute(): mixed;
}