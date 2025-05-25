<?php

namespace App\Helpers;
use Carbon\Carbon;
use InvalidArgumentException;

class Node
{
    public string $value;
    public ?Node $left = null;
    public ?Node $right = null;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}

class SortedStringSet
{
    private ?Node $root = null;
    private int $count = 0;

    public function __construct(array $elements = [])
    {
        foreach ($elements as $element) {
            $this->add($element);
        }
    }

    public static function fromArray(?array $elements): self
    {
        $elements = is_array($elements) && !empty($elements) ? $elements : [];
        $elements = array_unique($elements);
        return new self($elements);
    }

    public function add(string $element): bool
    {
        $element = trim($element);
        if (empty($element)) {
            throw new InvalidArgumentException("Element cannot be empty or whitespace only.");
        }

        if ($this->root === null) {
            $this->root = new Node($element);
            $this->count++;
            return true;
        }

        return $this->addRecursive($this->root, $element);
    }

    private function addRecursive(Node $node, string $element): bool
    {
        if ($element === $node->value) {
            return false;
        }

        if ($element < $node->value) {
            if ($node->left === null) {
                $node->left = new Node($element);
                $this->count++;
                return true;
            }
            return $this->addRecursive($node->left, $element);
        } else {
            if ($node->right === null) {
                $node->right = new Node($element);
                $this->count++;
                return true;
            }
            return $this->addRecursive($node->right, $element);
        }
    }

    public function addAll(array $elements): int
    {
        $added = 0;
        foreach ($elements as $element) {
            if ($this->add($element)) {
                $added++;
            }
        }
        return $added;
    }

    public function contains(string $element): bool
    {
        $element = trim($element);
        $currentNode = $this->root;

        while ($currentNode !== null) {
            if ($element === $currentNode->value) {
                return true;
            }
            $currentNode = $element < $currentNode->value ? $currentNode->left : $currentNode->right;
        }

        return false;
    }

    public function remove(string $element): bool
    {
        $element = trim($element);
        if (!$this->contains($element)) {
            return false;
        }

        $this->root = $this->removeRecursive($this->root, $element);
        return true;
    }

    private function removeRecursive(?Node $node, string $element): ?Node
    {
        if ($node === null) {
            return null;
        }

        if ($element < $node->value) {
            $node->left = $this->removeRecursive($node->left, $element);
        } elseif ($element > $node->value) {
            $node->right = $this->removeRecursive($node->right, $element);
        } else {
            $this->count--;

            if ($node->left === null) {
                return $node->right;
            } elseif ($node->right === null) {
                return $node->left;
            }

            $node->value = $this->findMinValue($node->right);
            $node->right = $this->removeRecursive($node->right, $node->value);
        }

        return $node;
    }

    public function removeAll(array $elements): int
    {
        $removed = 0;
        foreach ($elements as $element) {
            if ($this->remove($element)) {
                $removed++;
            }
        }
        return $removed;
    }

    private function findMinValue(Node $node): string
    {
        while ($node->left !== null) {
            $node = $node->left;
        }
        return $node->value;
    }

    public function getMin(): ?string
    {
        if ($this->root === null) {
            return null;
        }
        return $this->findMinValue($this->root);
    }

    public function getMax(): ?string
    {
        if ($this->root === null) {
            return null;
        }

        $current = $this->root;
        while ($current->right !== null) {
            $current = $current->right;
        }
        return $current->value;
    }

    public function count(): int
    {
        return $this->count;
    }

    public function isEmpty(): bool
    {
        return $this->count === 0;
    }

    public function clear(): void
    {
        $this->root = null;
        $this->count = 0;
    }

    public function toArray(): array
    {
        return $this->getElementsSorted();
    }

    public function getElementsSorted(?Carbon $from = null, ?Carbon $to = null): array
    {
        if ($from !== null && $to !== null && $from->gt($to)) {
            return [];
        }

        $elements = [];
        $fromString = $from?->format('Y-m-d');
        $toString = $to?->format('Y-m-d');

        $this->inOrderTraversal($this->root, $elements, $fromString, $toString);
        return $elements;
    }

    private function inOrderTraversal(?Node $node, array &$elements, ?string $fromString, ?string $toString): void
    {
        if ($node === null) {
            return;
        }

        if ($fromString === null || $node->value >= $fromString) {
            $this->inOrderTraversal($node->left, $elements, $fromString, $toString);
        }

        $isInRange = ($fromString === null || $node->value >= $fromString) &&
                     ($toString === null || $node->value <= $toString);

        if ($isInRange) {
            $elements[] = $node->value;
        }

        if ($toString === null || $node->value <= $toString) {
            $this->inOrderTraversal($node->right, $elements, $fromString, $toString);
        }
    }

    public function union(SortedStringSet $other): SortedStringSet
    {
        $result = new self($this->toArray());
        $result->addAll($other->toArray());
        return $result;
    }

    public function intersection(SortedStringSet $other): SortedStringSet
    {
        $result = new self();
        foreach ($this->toArray() as $element) {
            if ($other->contains($element)) {
                $result->add($element);
            }
        }
        return $result;
    }

    public function difference(SortedStringSet $other): SortedStringSet
    {
        $result = new self();
        foreach ($this->toArray() as $element) {
            if (!$other->contains($element)) {
                $result->add($element);
            }
        }
        return $result;
    }

    public function isSubsetOf(SortedStringSet $other): bool
    {
        if ($this->count > $other->count) {
            return false;
        }

        foreach ($this->toArray() as $element) {
            if (!$other->contains($element)) {
                return false;
            }
        }
        return true;
    }

    public function equals(SortedStringSet $other): bool
    {
        return $this->count === $other->count && $this->toArray() === $other->toArray();
    }

    public function getHeight(): int
    {
        return $this->calculateHeight($this->root);
    }

    private function calculateHeight(?Node $node): int
    {
        if ($node === null) {
            return 0;
        }
        return 1 + max($this->calculateHeight($node->left), $this->calculateHeight($node->right));
    }

    public function getElementsReversed(): array
    {
        $elements = [];
        $this->reverseInOrderTraversal($this->root, $elements);
        return $elements;
    }

    private function reverseInOrderTraversal(?Node $node, array &$elements): void
    {
        if ($node === null) {
            return;
        }

        $this->reverseInOrderTraversal($node->right, $elements);
        $elements[] = $node->value;
        $this->reverseInOrderTraversal($node->left, $elements);
    }

    public function clone(): SortedStringSet
    {
        return new self($this->toArray());
    }

    public function __toString(): string
    {
        return '[' . implode(', ', $this->toArray()) . ']';
    }

    public function subset(?Carbon $from = null, ?Carbon $to = null): SortedStringSet
    {
        $dates = $this->getElementsSorted($from, $to);
        return new self($dates);
    }
}