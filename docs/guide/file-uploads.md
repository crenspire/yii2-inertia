# File uploads

When the form data contains a `File`, Inertia sends the request as `multipart/form-data`. Yii handles it natively:

```jsx
const { data, setData, post, progress } = useForm({ name: '', avatar: null })

<input type="file" onChange={(e) => setData('avatar', e.target.files[0])} />
{progress && <progress value={progress.percentage} max="100" />}

post('/profile/avatar')
```

```php
use yii\web\UploadedFile;

public function actionAvatar(): Response
{
    $model = new AvatarForm();
    $model->avatar = UploadedFile::getInstanceByName('avatar');

    if (!$model->upload()) {
        Inertia::withErrors($model);

        return Inertia::back();
    }

    return $this->redirect(['profile/index']);
}
```

## Uploads with PUT or PATCH

PHP only parses `multipart/form-data` bodies for `POST` requests. Send a `POST` and spoof the method with the `_method`
field, which Yii reads through [`Request::$methodParam`](https://www.yiiframework.com/doc/api/2.0/yii-web-request#$methodParam-detail):

```jsx
router.post(`/users/${user.id}`, {
  _method: 'put',
  avatar: file,
})
```

The action then sees a `PUT` request and `UploadedFile::getInstanceByName('avatar')` works as usual.
