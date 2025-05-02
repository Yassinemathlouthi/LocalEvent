<?php
// Fix the isApproved field in Event entity

$eventEntityPath = __DIR__ . '/src/Entity/Event.php';
echo "Fixing isApproved field in Event entity...\n";

// Get current content
$content = file_get_contents($eventEntityPath);

// Check if we need to add the isApproved property
if (strpos($content, 'isApproved') === false) {
    // Find where to insert the new property (after the existing properties)
    $insertPoint = strpos($content, '#[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;');
    
    if ($insertPoint !== false) {
        // Add the isApproved property after createdAt
        $newProperty = "\n
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private ?bool \$isApproved = false;\n";
        
        // Insert the new property
        $content = substr_replace($content, $newProperty, $insertPoint + strlen('#[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;'), 0);
        
        // Now add the getter and setter methods before the last closing brace
        $lastBrace = strrpos($content, '}');
        
        $getterSetter = "\n    public function getIsApproved(): ?bool
    {
        return \$this->isApproved;
    }

    public function setIsApproved(bool \$isApproved): static
    {
        \$this->isApproved = \$isApproved;

        return \$this;
    }\n";
        
        $content = substr_replace($content, $getterSetter, $lastBrace, 0);
        
        // Save the updated content
        file_put_contents($eventEntityPath, $content);
        echo "✅ Added isApproved field to Event entity.\n";
    } else {
        echo "❌ Could not find insertion point for isApproved field.\n";
    }
} else {
    echo "isApproved field already exists in Event entity.\n";
}

echo "\nNow clear the cache with: php bin/console cache:clear\n";
?>
