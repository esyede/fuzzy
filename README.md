# fuzzy
Fuzzy search using [Levenstein Distance (LD)](https://en.wikipedia.org/wiki/Levenshtein_distance) and [Longest Common Substring (LCS)](https://en.wikipedia.org/wiki/Longest_common_substring_problem) algorithm (single file, no dependencies)



### Requirements
  - PHP 5.4 or newer



### Installation
Download the file from [release page](https://github.com/esyede/fuzzy/releases) and drop to your project. That's it.



### Usage example

```php
require 'Fuzzy.php';

$data = [
  ['name' => 'Halima Nasyidah', 'address' => 'Jln. Wahidin Sudirohusodo No. 483'],
  ['name' => 'Tiara Novitasari', 'address' => 'Gg. Kenanga No. 86'],
  ['name' => 'Irwan Balapati Nugroho', 'address' => 'Perum. Jamika No. 952'],
  ['name' => 'Dimas Marwata Napitupulu', 'address' => 'Kpg. Sijangkir No. 792']
];

$fuzzy = new Esyede\Fuzzy($data);

$keyword = 'Arah';
$attributes = 'name';

$results = $fuzzy->search($keyword, $attributes);
print_r($results);

$keyword = 'Na';
$attributes = ['name', 'address'];

$results = $fuzzy->search($keyword, $attributes);
print_r($results);
```

That's pretty much it. Thank you for stopping by!



### License
This library is licensed under the [MIT License](http://opensource.org/licenses/MIT)
