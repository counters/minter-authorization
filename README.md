# minter-authorization

## Examples
basic example

Install and test example
```bash
cd examples/basic # cd examples/custom_payload
composer install
vim index.php
php -S localhost:4444 # or run etc webserver
```


JSON object received in private response

```json
{
    "jsonrpc":
    "2.0", "id":
    "", "result":
    {
        "mx":"Mx0123****abcd", 
        "state":"3ec14fd7b42260743bcf9a65ff43eef5", 
        "verification":{"height": "1234567", "trs": "Mt*******"},
        "verifications":
        [
            {"height": "1234567", "trs": "Mt*******"}, 
            {"height": "2345678", "trs": "Mt*******"} 
        ]
    }
}
```
`verification` parameter will be displayed only when specifying the required payload