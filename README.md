# 게시판 전체 검색 모듈
게시판 모듈 내에서 타이틀, 본문, 댓글, 태그, 사용자 정의를 전체 검색할 수 있습니다.

### 주의사항
- 현재 해당 모듈은 alpha 버전 입니다. 사용 중 예기치 않은 문제를 발생할 수 있으니 실 사이트에서 사용에는 주의 하시기 바랍니다.
- 통합 검색을 구현하기 위해 서브쿼리와 컬럼 합치기 등을 시도하고 있습니다. 검색 시 SQL 성능 부하와 속도 저하가 발생될 수 있습니다.

### 설치 방법
- xe가 설치된 `/modules/` 안에 full_search 폴더를 복사합니다.
- 관리자 페이지 대시보드에 접속하여 full_search의 설정 완료하기를 클릭하여 모듈을 설치 합니다. <br> (document.getDocumentList before / after로 트리거가 추가 됩니다.)

### 사용 방법
- 관리자 페이지 - 셜치된 모듈에서 `게시판 통합 관리`로 이동하셔서 "게시판 전체 검색 사용하기"를 체크하세요.
- 현재 사용중인 게시판에서 검색을 시도하세요.

### 옵션
실제 사용을 위해 게시판의 검색 메뉴가 있는곳에 아래의 코드를 추가해주시면 좋습니다.

_(modules/board/skins/default/list.html)_
```html
<select name="search_target">
  <option loop="$search_option=>$key,$val" value="{$key}" selected="selected"|cond="$search_target==$key">{$val}</option>
  <option value="all_content" selected="selected"|cond="$search_target=='all_content'">{$lang->total}{$lang->cmd_search}</option>
  <option value="title_content_comment" selected="selected"|cond="$search_target=='title_content_comment'">{$lang->title}+{$lang->content}+{$lang->comment}</option>
  <option value="extra_vars" selected="selected"|cond="$search_target=='extra_vars'">{$lang->extra_vars}</option>
</select>
```

#### 선택 가능한 값

##### 파라미터

| 속성 | 기본 값 | 설명 |
| :-- | :----- | :-- |
| search_target | `title_content` | 아래는 XE가 기본적으로 설정 가능한 값을 제외하고 추가 된 속성 값 입니다. <br> `title_content_comment` <br> `title_content_tag` <br> `title_content_extra_vars` <br> `title_content_comment_tag` <br> `all_content` <br><br> 속성 값은 연속으로 나열 할 수 있습니다. <br> ``` &search_target=title,content,comment,extra_vars,... ``` |
| search_keyword | `null` | 검색어를 띄어쓰기를 이용해 최대 5개의 단어를 포함한 게시물을 검색 할 수 있습니다. <br><br> _예1) 게시판 등록 에러 안돼요_ <br>_예2) "게시판 등록" 에러 안돼요_ <br><br> ※ "(따옴표)안의 문장은 하나의 단어로 검색을 시도합니다.<br>※ 검색 키워드는 부분일치가 아니라 모든 단어를 포함할 경우 일치 합니다. |

##### 속성

| 값 | 설명 |
| :-- | :-- |
| `title_content` | 제목과 본문을 통합 검색 합니다. |
| `title_content_comment` | 제목과 본문, 댓글을 통합 검색 합니다. |
| `title_content_tag` | 제목과 본문, 태그를 통합 검색 합니다.  |
| `title_content_extra_vars` | 제목과 본문, 사용자 정의를 통합 검색 합니다. |
| `title_content_comment_tag` | 제목과 본문, 댓글, 태그를 통합 검색 합니다.  |
| `all_content` | 제목과 본문, 댓글, 태그, 사용자 정의를 통합 검색 합니다.  |
| `extra_vars` | 사용자 정의를 통합 검색 합니다. |
| `extra_vars[0-9]` | 사용자 정의 `var_idx`를 조건으로 검색 합니다. <br><br> 속성 값은 연속으로 나열 할 수 있습니다. <br> ``` &search_target=extra_vars1,extra_vars2,,... ``` |
| `사용자 정의` | xe가 제공하는 검색 옵션과 full search가 제공하는 검색 옵션을 제외한 속성 값은 사용자 정의 `eid`로 인식하여 검색을 시도 합니다.  <br><br> 속성 값은 연속으로 나열 할 수 있습니다. <br> ``` &search_target=age,address,,... ``` |

##### API

| 속성 | 기본 값 | 설명 |
| :-- | :----- | :-- |
| module | `full_search` | full_search 모듈명 |
| act | `getDocumentListTotal` | act명 |
| cur_mid | `null` | mid명 |

_API 호출 예(jquery)_
```javascript
$.ajax({
  url: request_uri,
  method: 'POST',
  data: {
    'module': 'full_search',
    'act': 'getDocumentListTotal',
    'cur_mid': 'mid',
    'search_target': 'all_content',
    'search_keyword': '키워드'
  },
  dataType: 'json',
  contentType: 'application/json'
})
.done(function (data) {
  console.log(data.document_list);
})
.fail(function (error) {
  console.log(error);
});
```

_API 호출 예(fetch)_
```javascript
let url_obj = new URL(request_uri);
let params = new URLSearchParams();
params.set('module', 'full_search');
params.set('act', 'getDocumentListTotal');
params.set('cur_mid', 'board');
params.set('search_target', 'all_content');
params.set('search_keyword', '키워드');

url_obj.search = params.toString();
fetch(url_obj.toString(), {
  method : 'POST',
  headers: {
    'Content-Type': 'application/json'
  }
})
.then((response) => {
  return response.json();
})
.then((data) => {
  console.log(data.document_list);
})
.catch((error) => {
  _throwError(error)
})
```

### 향후 개발 내용
목표로 하는 것은 github.com의 [Searching issues and pull requests](https://help.github.com/en/articles/searching-issues-and-pull-requests) 가 xe에서도 사용할 수 있게 하는 것 입니다. 현재는 특정 검색 대상으로 모든 단어를 검색하도록 시도하지만 향후 각 검색 대상에 맞는 검색어를 따로 정할 수 있게 하여 스킨에서 다양한 검색 조건을 만들 수 있는 것을 목표로 하고 있습니다.

test20240809
