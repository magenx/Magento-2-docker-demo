<?php

namespace Magento\PaymentServicesPaypal\Test\Unit\Helper;

use PHPUnit\Framework\TestCase;
use Magento\PaymentServicesPaypal\Helper\PaypalApiDataFormatter;

class PaypalApiDataFormatterTest extends TestCase
{

    public function testFormatName_longNameWithMultiByteCharacters(): void
    {
        $formatter = new PaypalApiDataFormatter();

        $name =
            "🅻🅾🆁🅴🅼·🅸🅿🆂🆄🅼·🅳🅾🅻🅾🆁·🆂🅸🆃·🅰🅼🅴🆃·🅲🅾🅽🆂🅴🅲🆃🅴🆃🆄🆁·🅰🅳🅸🅿🅸🆂🅲🅸🅽🅶·🅴🅻🅸🆃·" .
            "🅽🆄🅻🅻🅰·🅴🅻🅴🅼🅴🅽🆃🆄🅼·🅾🆁🅲🅸·🅽🅾🅽·🅲🅾🅽🆅🅰🅻🅻🅸🆂·🆁🆄🆃🆁🆄🅼·🅿🅴🅻🅻🅴🅽🆃🅴🆂🆀🆄🅴·" .
            "🆃🅾🆁🆃🅾🆁·🅰🆁🅲🆄·🅰🅻🅸🆀🆄🅴🆃·🅰·🅵🅰🆄🅲🅸🅱🆄🆂·🅴🆃·🅿🆁🅴🆃🅸🆄🅼·🅸🅳·🅾🅳🅸🅾·🅼🅰🆄🆁🅸🆂·" .
            "🅶🆁🅰🆅🅸🅳🅰·🆂🅴🅼·🆃🆄🆁🅿🅸🆂·🆃🅸🅽🅲🅸🅳🆄🅽🆃·🅻🅾🅱🅾🆁🆃🅸🆂·🅴🆂🆃·🅲🅾🅽🆂🅴🆀🆄🅰🆃·🅰🆃·🆂🅴🅳·" .
            "🅰🅲·🅸🅿🆂🆄🅼·🆅🅴🅻·🆃🆄🆁🅿🅸🆂·🆂🅴🅼🅿🅴🆁·🅼🅾🅻🅻🅸🆂·🆂🆄🆂🅿🅴🅽🅳🅸🆂🆂🅴·🆂🅾🅳🅰🅻🅴🆂·🆀🆄🅰🅼·" .
            "🅰🅲·🅴🅽🅸🅼·🅷🅴🅽🅳🆁🅴🆁🅸🆃·🆂🅴🅳·🅲🅾🅽🆅🅰🅻🅻🅸🆂·🅽🆄🅻🅻🅰·🅱🅸🅱🅴🅽🅳🆄🅼·🅳🅾🅽🅴🅲·🅴🆂🆃·" .
            "🆄🆁🅽🅰·🅿🅾🆂🆄🅴🆁🅴·🅽🅾🅽·🆄🆁🅽🅰·🅰·🅻🆄🅲🆃🆄🆂·🅰🆄🅲🆃🅾🆁·🅿🆄🆁🆄🆂·🅿🅴🅻🅻🅴🅽🆃🅴🆂🆀🆄🅴·🅻🅴🅾·" .
            "🅽🅸🆂🅻·🆂🅾🅻🅻🅸🅲🅸🆃🆄🅳🅸🅽·🅿🅷🅰🆁🅴🆃🆁🅰·🅴🆂🆃·🆂🅴🅳·🅼🅰🆃🆃🅸🆂·🆅🆄🅻🅿🆄🆃🅰🆃🅴·🅳🆄🅸·" .
            "🅰🅻🅸🆀🆄🅰🅼·🅻🅰🅾🆁🅴🅴🆃·🅴🅵🅵🅸🅲🅸🆃🆄🆁·🅽🅸🅱🅷·🅰🅲·🅼🅰🅻🅴🆂🆄🅰🅳🅰·🆂🅴🅳·🆅🅴🅽🅴🅽🅰🆃🅸🆂·" .
            "🅽🆄🅻🅻🅰·🅽🅾🅽·🅿🅴🅻🅻🅴🅽🆃🅴🆂🆀🆄🅴·🅵🅴🆁🅼🅴🅽🆃🆄🅼·🅰🅴🅽🅴🅰🅽·🅸🅽·🅸🅿🆂🆄🅼·🅻🅰🅲🅸🅽🅸🅰·" .
            "🅵🅰🅲🅸🅻🅸🆂🅸🆂·🅴🆇·🅰🅲·🅱🅸🅱🅴🅽🅳🆄🅼·🆃🆄🆁🅿🅸🆂";

        # Truncated to the first 127 (multibyte) characters
        $expectedFormattedName =
            "🅻🅾🆁🅴🅼·🅸🅿🆂🆄🅼·🅳🅾🅻🅾🆁·🆂🅸🆃·🅰🅼🅴🆃·🅲🅾🅽🆂🅴🅲🆃🅴🆃🆄🆁·🅰🅳🅸🅿🅸🆂🅲🅸🅽🅶·🅴🅻🅸🆃·" .
            "🅽🆄🅻🅻🅰·🅴🅻🅴🅼🅴🅽🆃🆄🅼·🅾🆁🅲🅸·🅽🅾🅽·🅲🅾🅽🆅🅰🅻🅻🅸🆂·🆁🆄🆃🆁🆄🅼·🅿🅴🅻🅻🅴🅽🆃🅴🆂🆀🆄🅴·" .
            "🆃🅾🆁🆃🅾🆁·🅰🆁🅲🆄·🅰🅻🅸🆀🆄";

        $actualFormattedName = $formatter->formatName($name);
        $this->assertEquals($expectedFormattedName, $actualFormattedName);
    }

    public function testFormatDescription_longDescriptionWithMultiByteCharactersAndHtmlTags(): void
    {
        $formatter = new PaypalApiDataFormatter();

        $description =
            "<p>🅻🅾🆁🅴🅼·🅸🅿🆂🆄🅼·🅳🅾🅻🅾🆁·🆂🅸🆃·🅰🅼🅴🆃·🅲🅾🅽🆂🅴🅲🆃🅴🆃🆄🆁·🅰🅳🅸🅿🅸🆂🅲🅸🅽🅶·🅴🅻🅸🆃·" .
            "🅽🆄🅻🅻🅰·🅴🅻🅴🅼🅴🅽🆃🆄🅼·🅾🆁🅲🅸·🅽🅾🅽·🅲🅾🅽🆅🅰🅻🅻🅸🆂·🆁🆄🆃🆁🆄🅼·🅿🅴🅻🅻🅴🅽🆃🅴🆂🆀🆄🅴·" .
            "🆃🅾🆁🆃🅾🆁·🅰🆁🅲🆄·🅰🅻🅸🆀🆄🅴🆃·🅰·🅵🅰🆄🅲🅸🅱🆄🆂·🅴🆃·🅿🆁🅴🆃🅸🆄🅼·🅸🅳·🅾🅳🅸🅾·🅼🅰🆄🆁🅸🆂·" .
            "🅶🆁🅰🆅🅸🅳🅰·🆂🅴🅼·🆃🆄🆁🅿🅸🆂·🆃🅸🅽🅲🅸🅳🆄🅽🆃·🅻🅾🅱🅾🆁🆃🅸🆂·🅴🆂🆃·🅲🅾🅽🆂🅴🆀🆄🅰🆃·🅰🆃·🆂🅴🅳·" .
            "🅰🅲·🅸🅿🆂🆄🅼·🆅🅴🅻·🆃🆄🆁🅿🅸🆂·🆂🅴🅼🅿🅴🆁·🅼🅾🅻🅻🅸🆂·🆂🆄🆂🅿🅴🅽🅳🅸🆂🆂🅴·🆂🅾🅳🅰🅻🅴🆂·🆀🆄🅰🅼·" .
            "🅰🅲·🅴🅽🅸🅼·🅷🅴🅽🅳🆁🅴🆁🅸🆃·🆂🅴🅳·🅲🅾🅽🆅🅰🅻🅻🅸🆂·🅽🆄🅻🅻🅰·🅱🅸🅱🅴🅽🅳🆄🅼·🅳🅾🅽🅴🅲·🅴🆂🆃·" .
            "🆄🆁🅽🅰·🅿🅾🆂🆄🅴🆁🅴·🅽🅾🅽·🆄🆁🅽🅰·🅰·🅻🆄🅲🆃🆄🆂·🅰🆄🅲🆃🅾🆁·🅿🆄🆁🆄🆂·🅿🅴🅻🅻🅴🅽🆃🅴🆂🆀🆄🅴·🅻🅴🅾·" .
            "🅽🅸🆂🅻·🆂🅾🅻🅻🅸🅲🅸🆃🆄🅳🅸🅽·🅿🅷🅰🆁🅴🆃🆁🅰·🅴🆂🆃·🆂🅴🅳·🅼🅰🆃🆃🅸🆂·🆅🆄🅻🅿🆄🆃🅰🆃🅴·🅳🆄🅸·" .
            "🅰🅻🅸🆀🆄🅰🅼·🅻🅰🅾🆁🅴🅴🆃·🅴🅵🅵🅸🅲🅸🆃🆄🆁·🅽🅸🅱🅷·🅰🅲·🅼🅰🅻🅴🆂🆄🅰🅳🅰·🆂🅴🅳·🆅🅴🅽🅴🅽🅰🆃🅸🆂·" .
            "🅽🆄🅻🅻🅰·🅽🅾🅽·🅿🅴🅻🅻🅴🅽🆃🅴🆂🆀🆄🅴·🅵🅴🆁🅼🅴🅽🆃🆄🅼·🅰🅴🅽🅴🅰🅽·🅸🅽·🅸🅿🆂🆄🅼·🅻🅰🅲🅸🅽🅸🅰·" .
            "🅵🅰🅲🅸🅻🅸🆂🅸🆂·🅴🆇·🅰🅲·🅱🅸🅱🅴🅽🅳🆄🅼·🆃🆄🆁🅿🅸🆂·</p>";

        # Html tags stripped and truncated to the first 127 (multibyte) characters
        $expectedFormattedDescription =
            "🅻🅾🆁🅴🅼·🅸🅿🆂🆄🅼·🅳🅾🅻🅾🆁·🆂🅸🆃·🅰🅼🅴🆃·🅲🅾🅽🆂🅴🅲🆃🅴🆃🆄🆁·🅰🅳🅸🅿🅸🆂🅲🅸🅽🅶·🅴🅻🅸🆃·" .
            "🅽🆄🅻🅻🅰·🅴🅻🅴🅼🅴🅽🆃🆄🅼·🅾🆁🅲🅸·🅽🅾🅽·🅲🅾🅽🆅🅰🅻🅻🅸🆂·🆁🆄🆃🆁🆄🅼·🅿🅴🅻🅻🅴🅽🆃🅴🆂🆀🆄🅴·" .
            "🆃🅾🆁🆃🅾🆁·🅰🆁🅲🆄·🅰🅻🅸🆀🆄";

        $actualFormattedDescription = $formatter->formatDescription($description);
        $this->assertEquals($expectedFormattedDescription, $actualFormattedDescription);
    }
}
