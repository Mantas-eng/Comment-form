<?php return array (
  0 => 
  array (
    'GET' => 
    array (
      '/EspoCRM/api/v1/Activities/upcoming' => 'route3',
      '/EspoCRM/api/v1/Activities' => 'route4',
      '/EspoCRM/api/v1/Timeline' => 'route5',
      '/EspoCRM/api/v1/Timeline/busyRanges' => 'route6',
      '/EspoCRM/api/v1/' => 'route15',
      '/EspoCRM/api/v1/App/user' => 'route16',
      '/EspoCRM/api/v1/App/about' => 'route18',
      '/EspoCRM/api/v1/Metadata' => 'route19',
      '/EspoCRM/api/v1/I18n' => 'route20',
      '/EspoCRM/api/v1/Settings' => 'route21',
      '/EspoCRM/api/v1/Stream' => 'route24',
      '/EspoCRM/api/v1/GlobalStream' => 'route25',
      '/EspoCRM/api/v1/GlobalSearch' => 'route26',
      '/EspoCRM/api/v1/Admin/jobs' => 'route38',
      '/EspoCRM/api/v1/CurrencyRate' => 'route44',
      '/EspoCRM/api/v1/Email/inbox/notReadCounts' => 'route79',
      '/EspoCRM/api/v1/Email/insertFieldData' => 'route80',
      '/EspoCRM/api/v1/EmailAddress/search' => 'route82',
      '/EspoCRM/api/v1/Oidc/authorizationData' => 'route92',
    ),
    'POST' => 
    array (
      '/EspoCRM/api/v1/App/destroyAuthToken' => 'route17',
      '/EspoCRM/api/v1/Admin/rebuild' => 'route36',
      '/EspoCRM/api/v1/Admin/clearCache' => 'route37',
      '/EspoCRM/api/v1/Action' => 'route46',
      '/EspoCRM/api/v1/MassAction' => 'route47',
      '/EspoCRM/api/v1/Export' => 'route50',
      '/EspoCRM/api/v1/Import' => 'route53',
      '/EspoCRM/api/v1/Import/file' => 'route54',
      '/EspoCRM/api/v1/Attachment/fromImageUrl' => 'route63',
      '/EspoCRM/api/v1/Email/importEml' => 'route70',
      '/EspoCRM/api/v1/Email/sendTest' => 'route71',
      '/EspoCRM/api/v1/Email/inbox/read' => 'route72',
      '/EspoCRM/api/v1/Email/inbox/important' => 'route74',
      '/EspoCRM/api/v1/Email/inbox/inTrash' => 'route76',
      '/EspoCRM/api/v1/UserSecurity/apiKey/generate' => 'route85',
      '/EspoCRM/api/v1/UserSecurity/password/recovery' => 'route87',
      '/EspoCRM/api/v1/UserSecurity/password/generate' => 'route88',
      '/EspoCRM/api/v1/User/passwordChangeRequest' => 'route89',
      '/EspoCRM/api/v1/User/changePasswordByRequest' => 'route90',
      '/EspoCRM/api/v1/Oidc/backchannelLogout' => 'route93',
    ),
    'PATCH' => 
    array (
      '/EspoCRM/api/v1/Settings' => 'route22',
    ),
    'PUT' => 
    array (
      '/EspoCRM/api/v1/Settings' => 'route23',
      '/EspoCRM/api/v1/CurrencyRate' => 'route45',
      '/EspoCRM/api/v1/Kanban/order' => 'route59',
      '/EspoCRM/api/v1/UserSecurity/password' => 'route86',
    ),
    'DELETE' => 
    array (
      '/EspoCRM/api/v1/Email/inbox/read' => 'route73',
      '/EspoCRM/api/v1/Email/inbox/important' => 'route75',
      '/EspoCRM/api/v1/Email/inbox/inTrash' => 'route77',
    ),
  ),
  1 => 
  array (
    'GET' => 
    array (
      0 => 
      array (
        'regex' => '~^(?|/EspoCRM/api/v1/Activities/([^/]+)/([^/]+)/composeEmailAddressList|/EspoCRM/api/v1/Activities/([^/]+)/([^/]+)/([^/]+)|/EspoCRM/api/v1/Activities/([^/]+)/([^/]+)/([^/]+)/list/([^/]+)|/EspoCRM/api/v1/Meeting/([^/]+)/attendees()()()()|/EspoCRM/api/v1/Call/([^/]+)/attendees()()()()()|/EspoCRM/api/v1/TargetList/([^/]+)/optedOut()()()()()()|/EspoCRM/api/v1/([^/]+)/action/([^/]+)()()()()()()|/EspoCRM/api/v1/([^/]+)/layout/([^/]+)()()()()()()()|/EspoCRM/api/v1/Admin/fieldManager/([^/]+)/([^/]+)()()()()()()()()|/EspoCRM/api/v1/MassAction/([^/]+)/status()()()()()()()()()()|/EspoCRM/api/v1/Export/([^/]+)/status()()()()()()()()()()()|/EspoCRM/api/v1/Kanban/([^/]+)()()()()()()()()()()()())$~',
        'routeMap' => 
        array (
          3 => 
          array (
            0 => 'route0',
            1 => 
            array (
              'parentType' => 'parentType',
              'id' => 'id',
            ),
          ),
          4 => 
          array (
            0 => 'route1',
            1 => 
            array (
              'parentType' => 'parentType',
              'id' => 'id',
              'type' => 'type',
            ),
          ),
          5 => 
          array (
            0 => 'route2',
            1 => 
            array (
              'parentType' => 'parentType',
              'id' => 'id',
              'type' => 'type',
              'targetType' => 'targetType',
            ),
          ),
          6 => 
          array (
            0 => 'route7',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          7 => 
          array (
            0 => 'route8',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          8 => 
          array (
            0 => 'route10',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          9 => 
          array (
            0 => 'route32',
            1 => 
            array (
              'controller' => 'controller',
              'action' => 'action',
            ),
          ),
          10 => 
          array (
            0 => 'route33',
            1 => 
            array (
              'controller' => 'controller',
              'name' => 'name',
            ),
          ),
          11 => 
          array (
            0 => 'route39',
            1 => 
            array (
              'scope' => 'scope',
              'name' => 'name',
            ),
          ),
          12 => 
          array (
            0 => 'route48',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          13 => 
          array (
            0 => 'route51',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          14 => 
          array (
            0 => 'route60',
            1 => 
            array (
              'entityType' => 'entityType',
            ),
          ),
        ),
      ),
      1 => 
      array (
        'regex' => '~^(?|/EspoCRM/api/v1/Attachment/file/([^/]+)|/EspoCRM/api/v1/Note/([^/]+)/reactors/([^/]+)|/EspoCRM/api/v1/User/([^/]+)/stream/own()()|/EspoCRM/api/v1/User/([^/]+)/acl()()()|/EspoCRM/api/v1/([^/]+)/([^/]+)()()()|/EspoCRM/api/v1/([^/]+)()()()()()|/EspoCRM/api/v1/([^/]+)/([^/]+)/followers()()()()()|/EspoCRM/api/v1/([^/]+)/([^/]+)/stream()()()()()()|/EspoCRM/api/v1/([^/]+)/([^/]+)/posts()()()()()()()|/EspoCRM/api/v1/([^/]+)/([^/]+)/updateStream()()()()()()()()|/EspoCRM/api/v1/([^/]+)/([^/]+)/usersAccess()()()()()()()()()|/EspoCRM/api/v1/([^/]+)/([^/]+)/([^/]+)()()()()()()()()())$~',
        'routeMap' => 
        array (
          2 => 
          array (
            0 => 'route61',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          3 => 
          array (
            0 => 'route67',
            1 => 
            array (
              'id' => 'id',
              'type' => 'type',
            ),
          ),
          4 => 
          array (
            0 => 'route83',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          5 => 
          array (
            0 => 'route84',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          6 => 
          array (
            0 => 'route94',
            1 => 
            array (
              'controller' => 'controller',
              'id' => 'id',
            ),
          ),
          7 => 
          array (
            0 => 'route95',
            1 => 
            array (
              'controller' => 'controller',
            ),
          ),
          8 => 
          array (
            0 => 'route100',
            1 => 
            array (
              'entityType' => 'entityType',
              'id' => 'id',
            ),
          ),
          9 => 
          array (
            0 => 'route103',
            1 => 
            array (
              'controller' => 'controller',
              'id' => 'id',
            ),
          ),
          10 => 
          array (
            0 => 'route104',
            1 => 
            array (
              'controller' => 'controller',
              'id' => 'id',
            ),
          ),
          11 => 
          array (
            0 => 'route105',
            1 => 
            array (
              'controller' => 'controller',
              'id' => 'id',
            ),
          ),
          12 => 
          array (
            0 => 'route112',
            1 => 
            array (
              'entityType' => 'entityType',
              'id' => 'id',
            ),
          ),
          13 => 
          array (
            0 => 'route113',
            1 => 
            array (
              'controller' => 'controller',
              'id' => 'id',
              'link' => 'link',
            ),
          ),
        ),
      ),
    ),
    'POST' => 
    array (
      0 => 
      array (
        'regex' => '~^(?|/EspoCRM/api/v1/Campaign/([^/]+)/generateMailMerge|/EspoCRM/api/v1/Campaign/unsubscribe/([^/]+)()|/EspoCRM/api/v1/Campaign/unsubscribe/([^/]+)/([^/]+)()|/EspoCRM/api/v1/LeadCapture/form/([^/]+)()()()|/EspoCRM/api/v1/LeadCapture/([^/]+)()()()()|/EspoCRM/api/v1/([^/]+)/action/([^/]+)()()()()|/EspoCRM/api/v1/Admin/fieldManager/([^/]+)()()()()()()|/EspoCRM/api/v1/MassAction/([^/]+)/subscribe()()()()()()()|/EspoCRM/api/v1/Export/([^/]+)/subscribe()()()()()()()()|/EspoCRM/api/v1/Import/([^/]+)/revert()()()()()()()()()|/EspoCRM/api/v1/Import/([^/]+)/removeDuplicates()()()()()()()()()()|/EspoCRM/api/v1/Import/([^/]+)/unmarkDuplicates()()()()()()()()()()())$~',
        'routeMap' => 
        array (
          2 => 
          array (
            0 => 'route9',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          3 => 
          array (
            0 => 'route11',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          4 => 
          array (
            0 => 'route13',
            1 => 
            array (
              'emailAddress' => 'emailAddress',
              'hash' => 'hash',
            ),
          ),
          5 => 
          array (
            0 => 'route27',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          6 => 
          array (
            0 => 'route28',
            1 => 
            array (
              'apiKey' => 'apiKey',
            ),
          ),
          7 => 
          array (
            0 => 'route30',
            1 => 
            array (
              'controller' => 'controller',
              'action' => 'action',
            ),
          ),
          8 => 
          array (
            0 => 'route40',
            1 => 
            array (
              'scope' => 'scope',
            ),
          ),
          9 => 
          array (
            0 => 'route49',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          10 => 
          array (
            0 => 'route52',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          11 => 
          array (
            0 => 'route55',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          12 => 
          array (
            0 => 'route56',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          13 => 
          array (
            0 => 'route57',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
        ),
      ),
      1 => 
      array (
        'regex' => '~^(?|/EspoCRM/api/v1/Import/([^/]+)/exportErrors|/EspoCRM/api/v1/Attachment/chunk/([^/]+)()|/EspoCRM/api/v1/Attachment/copy/([^/]+)()()|/EspoCRM/api/v1/Note/([^/]+)/myReactions/([^/]+)()()|/EspoCRM/api/v1/EmailTemplate/([^/]+)/prepare()()()()|/EspoCRM/api/v1/Email/([^/]+)/attachments/copy()()()()()|/EspoCRM/api/v1/Email/inbox/folders/([^/]+)()()()()()()|/EspoCRM/api/v1/Email/([^/]+)/users()()()()()()()|/EspoCRM/api/v1/([^/]+)()()()()()()()()|/EspoCRM/api/v1/([^/]+)/([^/]+)/followers()()()()()()()()|/EspoCRM/api/v1/([^/]+)/([^/]+)/pin()()()()()()()()()|/EspoCRM/api/v1/([^/]+)/([^/]+)/([^/]+)()()()()()()()()())$~',
        'routeMap' => 
        array (
          2 => 
          array (
            0 => 'route58',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          3 => 
          array (
            0 => 'route62',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          4 => 
          array (
            0 => 'route64',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          5 => 
          array (
            0 => 'route65',
            1 => 
            array (
              'id' => 'id',
              'type' => 'type',
            ),
          ),
          6 => 
          array (
            0 => 'route68',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          7 => 
          array (
            0 => 'route69',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          8 => 
          array (
            0 => 'route78',
            1 => 
            array (
              'folderId' => 'folderId',
            ),
          ),
          9 => 
          array (
            0 => 'route81',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          10 => 
          array (
            0 => 'route96',
            1 => 
            array (
              'controller' => 'controller',
            ),
          ),
          11 => 
          array (
            0 => 'route101',
            1 => 
            array (
              'entityType' => 'entityType',
              'id' => 'id',
            ),
          ),
          12 => 
          array (
            0 => 'route108',
            1 => 
            array (
              'Note' => 'Note',
              'id' => 'id',
            ),
          ),
          13 => 
          array (
            0 => 'route114',
            1 => 
            array (
              'controller' => 'controller',
              'id' => 'id',
              'link' => 'link',
            ),
          ),
        ),
      ),
    ),
    'DELETE' => 
    array (
      0 => 
      array (
        'regex' => '~^(?|/EspoCRM/api/v1/Campaign/unsubscribe/([^/]+)|/EspoCRM/api/v1/Campaign/unsubscribe/([^/]+)/([^/]+)|/EspoCRM/api/v1/Admin/fieldManager/([^/]+)/([^/]+)()|/EspoCRM/api/v1/Note/([^/]+)/myReactions/([^/]+)()()|/EspoCRM/api/v1/([^/]+)/([^/]+)()()()|/EspoCRM/api/v1/([^/]+)/([^/]+)/followers()()()()|/EspoCRM/api/v1/([^/]+)/([^/]+)/subscription()()()()()|/EspoCRM/api/v1/([^/]+)/([^/]+)/pin()()()()()()|/EspoCRM/api/v1/([^/]+)/([^/]+)/starSubscription()()()()()()()|/EspoCRM/api/v1/([^/]+)/([^/]+)/([^/]+)()()()()()()())$~',
        'routeMap' => 
        array (
          2 => 
          array (
            0 => 'route12',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          3 => 
          array (
            0 => 'route14',
            1 => 
            array (
              'emailAddress' => 'emailAddress',
              'hash' => 'hash',
            ),
          ),
          4 => 
          array (
            0 => 'route43',
            1 => 
            array (
              'scope' => 'scope',
              'name' => 'name',
            ),
          ),
          5 => 
          array (
            0 => 'route66',
            1 => 
            array (
              'id' => 'id',
              'type' => 'type',
            ),
          ),
          6 => 
          array (
            0 => 'route99',
            1 => 
            array (
              'controller' => 'controller',
              'id' => 'id',
            ),
          ),
          7 => 
          array (
            0 => 'route102',
            1 => 
            array (
              'entityType' => 'entityType',
              'id' => 'id',
            ),
          ),
          8 => 
          array (
            0 => 'route107',
            1 => 
            array (
              'controller' => 'controller',
              'id' => 'id',
            ),
          ),
          9 => 
          array (
            0 => 'route109',
            1 => 
            array (
              'Note' => 'Note',
              'id' => 'id',
            ),
          ),
          10 => 
          array (
            0 => 'route111',
            1 => 
            array (
              'entityType' => 'entityType',
              'id' => 'id',
            ),
          ),
          11 => 
          array (
            0 => 'route115',
            1 => 
            array (
              'controller' => 'controller',
              'id' => 'id',
              'link' => 'link',
            ),
          ),
        ),
      ),
    ),
    'OPTIONS' => 
    array (
      0 => 
      array (
        'regex' => '~^(?|/EspoCRM/api/v1/LeadCapture/([^/]+))$~',
        'routeMap' => 
        array (
          2 => 
          array (
            0 => 'route29',
            1 => 
            array (
              'apiKey' => 'apiKey',
            ),
          ),
        ),
      ),
    ),
    'PUT' => 
    array (
      0 => 
      array (
        'regex' => '~^(?|/EspoCRM/api/v1/([^/]+)/action/([^/]+)|/EspoCRM/api/v1/([^/]+)/layout/([^/]+)()|/EspoCRM/api/v1/([^/]+)/layout/([^/]+)/([^/]+)()|/EspoCRM/api/v1/Admin/fieldManager/([^/]+)/([^/]+)()()()|/EspoCRM/api/v1/Team/([^/]+)/userPosition()()()()()|/EspoCRM/api/v1/([^/]+)/([^/]+)()()()()()|/EspoCRM/api/v1/([^/]+)/([^/]+)/subscription()()()()()()|/EspoCRM/api/v1/([^/]+)/([^/]+)/starSubscription()()()()()()())$~',
        'routeMap' => 
        array (
          3 => 
          array (
            0 => 'route31',
            1 => 
            array (
              'controller' => 'controller',
              'action' => 'action',
            ),
          ),
          4 => 
          array (
            0 => 'route34',
            1 => 
            array (
              'controller' => 'controller',
              'name' => 'name',
            ),
          ),
          5 => 
          array (
            0 => 'route35',
            1 => 
            array (
              'controller' => 'controller',
              'name' => 'name',
              'setId' => 'setId',
            ),
          ),
          6 => 
          array (
            0 => 'route41',
            1 => 
            array (
              'scope' => 'scope',
              'name' => 'name',
            ),
          ),
          7 => 
          array (
            0 => 'route91',
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          8 => 
          array (
            0 => 'route97',
            1 => 
            array (
              'controller' => 'controller',
              'id' => 'id',
            ),
          ),
          9 => 
          array (
            0 => 'route106',
            1 => 
            array (
              'controller' => 'controller',
              'id' => 'id',
            ),
          ),
          10 => 
          array (
            0 => 'route110',
            1 => 
            array (
              'entityType' => 'entityType',
              'id' => 'id',
            ),
          ),
        ),
      ),
    ),
    'PATCH' => 
    array (
      0 => 
      array (
        'regex' => '~^(?|/EspoCRM/api/v1/Admin/fieldManager/([^/]+)/([^/]+)|/EspoCRM/api/v1/([^/]+)/([^/]+)())$~',
        'routeMap' => 
        array (
          3 => 
          array (
            0 => 'route42',
            1 => 
            array (
              'scope' => 'scope',
              'name' => 'name',
            ),
          ),
          4 => 
          array (
            0 => 'route98',
            1 => 
            array (
              'controller' => 'controller',
              'id' => 'id',
            ),
          ),
        ),
      ),
    ),
  ),
);