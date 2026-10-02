<?php

arch('aucun appel de débogage oublié')->expect(['dd', 'dump', 'ray', 'var_dump'])->not->toBeUsed();
