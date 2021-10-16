<x-tests.app>
  <x-slot name="header">ヘッダー１</x-slot>

<x-tests.card title="タイトル" content=”コンテント” :message="$message" />
<x-tests.card title="タイトル2"  />
<x-tests.card title="CSSを変更したい"  class="bg-red-300" />
コンポーネントテスト１
</x-tests.app>
