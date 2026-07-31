<?php

class GlobalContext {
    protected static ?Context $root_ctx = NULL;
    
    public static function getCurrent() : Context {
        $cur_ctx = self::$root_ctx;
        while ($cur_ctx->getChild() !== NULL) {
            $cur_ctx = $cur_ctx->getChild();
        }

        return $cur_ctx;
    }

    public static function init() {
        if (self::$root_ctx !== NULL) {
            throw new RackTablesError("The context has already been initialized");
        }

        self::$root_ctx = new Context();
    }
}

class Context {
    protected array $values = [];
    protected ?Context $parent = NULL;
    protected ?Context $child = NULL;

    function __construct(?Context $parent = NULL, array $values = []) {
        $this->parent = $parent;
        $this->values = $values;
    }

    public function new(array $values) : Context {
        if ($this->child !== NULL) {
            throw new RackTablesError("The context already has a child");
        }

        $new_ctx = new self($this, $values);
        $this->child = $new_ctx;
        return $new_ctx;
    }

    public function deactivate() {
        if ($this->parent === NULL) {
            throw new RackTablesError("The root context cannot be deactivated");
        }

        $this->parent->child = NULL;
    }

    public function getChild() : ?Context {
        return $this->child;
    }

    public function values() : array {
        if ($this->parent == NULL) {
            return $this->values;
        }

        return array_merge($this->parent->values(), $this->values);
    }
}

GlobalContext::init();
