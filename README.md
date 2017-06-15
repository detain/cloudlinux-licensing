# Cloudlinux Licensing API
![Logo](https://www.cloudlinux.com/images/icons/prod_cl_logo.svg) 
Class to interface with the Cloudlinux Licensing API to manage Cloudlinux and KernelCare License Types.  More info at https://www.cloudlinux.com/

Travis-CI: [![Build Status](https://travis-ci.org/detain/cloudlinux-licensing.svg?branch=master)](https://travis-ci.org/detain/cloudlinux-licensing)
Code Climate: [![Code Climate](https://codeclimate.com/github/detain/cloudlinux-licensing/badges/gpa.svg)](https://codeclimate.com/github/detain/cloudlinux-licensing) [![Test Coverage](https://codeclimate.com/github/detain/cloudlinux-licensing/badges/coverage.svg)](https://codeclimate.com/github/detain/cloudlinux-licensing/coverage) [![Issue Count](https://codeclimate.com/github/detain/cloudlinux-licensing/badges/issue_count.svg)](https://codeclimate.com/github/detain/cloudlinux-licensing)
Scrutinizer: [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/detain/cloudlinux-licensing/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/detain/cloudlinux-licensing/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/detain/cloudlinux-licensing/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/detain/cloudlinux-licensing/?branch=master) [![Build Status](https://scrutinizer-ci.com/g/detain/cloudlinux-licensing/badges/build.png?b=master)](https://scrutinizer-ci.com/g/detain/cloudlinux-licensing/build-status/master)
Codacy: [![Codacy Badge](https://api.codacy.com/project/badge/Grade/226251fc068f4fd5b4b4ef9a40011d06)](https://www.codacy.com/app/detain/cloudlinux-licensing) [![Codacy Badge](https://api.codacy.com/project/badge/Coverage/25fa74eb74c947bf969602fcfe87e349)](https://www.codacy.com/app/detain/cloudlinux-licensing?utm_source=github.com&utm_medium=referral&utm_content=detain/cloudlinux-licensing&utm_campaign=Badge_Coverage)
VersionEye: [![Reference Status](https://www.versioneye.com/php/detain:cloudlinux-licensing/reference_badge.svg?style=flat)](https://www.versioneye.com/php/detain:cloudlinux-licensing/references) [![Dependency Status](https://www.versioneye.com/user/projects/592f7318bafc5500414dfd2a/badge.svg?style=flat-square)](https://www.versioneye.com/user/projects/592f7318bafc5500414dfd2a)
Packagist: [![Latest Stable Version](https://poser.pugx.org/detain/cloudlinux-licensing/version)](https://packagist.org/packages/detain/cloudlinux-licensing) [![Total Downloads](https://poser.pugx.org/detain/cloudlinux-licensing/downloads)](https://packagist.org/packages/detain/cloudlinux-licensing) [![Latest Unstable Version](https://poser.pugx.org/detain/cloudlinux-licensing/v/unstable)](//packagist.org/packages/detain/cloudlinux-licensing) [![Monthly Downloads](https://poser.pugx.org/detain/cloudlinux-licensing/d/monthly)](https://packagist.org/packages/detain/cloudlinux-licensing) [![Daily Downloads](https://poser.pugx.org/detain/cloudlinux-licensing/d/daily)](https://packagist.org/packages/detain/cloudlinux-licensing) [![License](https://poser.pugx.org/detain/cloudlinux-licensing/license)](https://packagist.org/packages/detain/cloudlinux-licensing)

## Installation

Install with composer like

```sh
composer require detain/cloudlinux-licensing
```

## Basic Usage

### Initialization

Initialize passing the API credentials like

```php
use Detain\Cloudlinux\Cloudlinux;

$cloudlinux = new Cloudlinux('API Username', 'API Password');
```

### List Licensed IPs

```php
foreach ($cl->reconcile() as $license)
	echo $license['IP'] . ' is type ' . $license['TYPE'] . '. server registered in CLN with license: ' . var_export($license['REGISTERED'], true) . "\n";
```

## License

Cloudlinux Licensing class is licensed under the LGPL-v2 license.

