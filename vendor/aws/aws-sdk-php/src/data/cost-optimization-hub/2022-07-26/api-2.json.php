<?php
// This file was auto-generated from sdk-root/src/data/cost-optimization-hub/2022-07-26/api-2.json
return [ 'version' => '2.0', 'metadata' => [ 'apiVersion' => '2022-07-26', 'auth' => [ 'aws.auth#sigv4', ], 'endpointPrefix' => 'cost-optimization-hub', 'jsonVersion' => '1.0', 'protocol' => 'json', 'protocols' => [ 'json', ], 'serviceFullName' => 'Cost Optimization Hub', 'serviceId' => 'Cost Optimization Hub', 'signatureVersion' => 'v4', 'signingName' => 'cost-optimization-hub', 'targetPrefix' => 'CostOptimizationHubService', 'uid' => 'cost-optimization-hub-2022-07-26', ], 'operations' => [ 'GetPreferences' => [ 'name' => 'GetPreferences', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'GetPreferencesRequest', ], 'output' => [ 'shape' => 'GetPreferencesResponse', ], 'errors' => [ [ 'shape' => 'ValidationException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'GetRecommendation' => [ 'name' => 'GetRecommendation', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'GetRecommendationRequest', ], 'output' => [ 'shape' => 'GetRecommendationResponse', ], 'errors' => [ [ 'shape' => 'ValidationException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ResourceNotFoundException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'ListEnrollmentStatuses' => [ 'name' => 'ListEnrollmentStatuses', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'ListEnrollmentStatusesRequest', ], 'output' => [ 'shape' => 'ListEnrollmentStatusesResponse', ], 'errors' => [ [ 'shape' => 'ValidationException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'ListRecommendationSummaries' => [ 'name' => 'ListRecommendationSummaries', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'ListRecommendationSummariesRequest', ], 'output' => [ 'shape' => 'ListRecommendationSummariesResponse', ], 'errors' => [ [ 'shape' => 'ValidationException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'ListRecommendations' => [ 'name' => 'ListRecommendations', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'ListRecommendationsRequest', ], 'output' => [ 'shape' => 'ListRecommendationsResponse', ], 'errors' => [ [ 'shape' => 'ValidationException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'UpdateEnrollmentStatus' => [ 'name' => 'UpdateEnrollmentStatus', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'UpdateEnrollmentStatusRequest', ], 'output' => [ 'shape' => 'UpdateEnrollmentStatusResponse', ], 'errors' => [ [ 'shape' => 'ValidationException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ThrottlingException', ], ], ], 'UpdatePreferences' => [ 'name' => 'UpdatePreferences', 'http' => [ 'method' => 'POST', 'requestUri' => '/', ], 'input' => [ 'shape' => 'UpdatePreferencesRequest', ], 'output' => [ 'shape' => 'UpdatePreferencesResponse', ], 'errors' => [ [ 'shape' => 'ValidationException', ], [ 'shape' => 'InternalServerException', ], [ 'shape' => 'AccessDeniedException', ], [ 'shape' => 'ThrottlingException', ], ], ], ], 'shapes' => [ 'AccessDeniedException' => [ 'type' => 'structure', 'required' => [ 'message', ], 'members' => [ 'message' => [ 'shape' => 'String', ], ], 'exception' => true, ], 'AccountEnrollmentStatus' => [ 'type' => 'structure', 'members' => [ 'accountId' => [ 'shape' => 'AccountId', ], 'status' => [ 'shape' => 'EnrollmentStatus', ], 'lastUpdatedTimestamp' => [ 'shape' => 'Timestamp', ], 'createdTimestamp' => [ 'shape' => 'Timestamp', ], ], ], 'AccountEnrollmentStatuses' => [ 'type' => 'list', 'member' => [ 'shape' => 'AccountEnrollmentStatus', ], ], 'AccountId' => [ 'type' => 'string', 'pattern' => '[0-9]{12}', ], 'AccountIdList' => [ 'type' => 'list', 'member' => [ 'shape' => 'AccountId', ], 'max' => 100, 'min' => 1, ], 'ActionType' => [ 'type' => 'string', 'enum' => [ 'Rightsize', 'Stop', 'Upgrade', 'PurchaseSavingsPlans', 'PurchaseReservedInstances', 'MigrateToGraviton', 'Delete', 'ScaleIn', ], ], 'ActionTypeList' => [ 'type' => 'list', 'member' => [ 'shape' => 'ActionType', ], 'max' => 100, 'min' => 1, ], 'AllocationStrategy' => [ 'type' => 'string', 'enum' => [ 'Prioritized', 'LowestPrice', ], ], 'BlockStoragePerformanceConfiguration' => [ 'type' => 'structure', 'members' => [ 'iops' => [ 'shape' => 'Double', ], 'throughput' => [ 'shape' => 'Double', ], ], ], 'Boolean' => [ 'type' => 'boolean', 'box' => true, ], 'ComputeConfiguration' => [ 'type' => 'structure', 'members' => [ 'vCpu' => [ 'shape' => 'Double', ], 'memorySizeInMB' => [ 'shape' => 'Integer', ], 'architecture' => [ 'shape' => 'String', ], 'platform' => [ 'shape' => 'String', ], ], ], 'ComputeSavingsPlans' => [ 'type' => 'structure', 'members' => [ 'configuration' => [ 'shape' => 'ComputeSavingsPlansConfiguration', ], 'costCalculation' => [ 'shape' => 'SavingsPlansCostCalculation', ], ], ], 'ComputeSavingsPlansConfiguration' => [ 'type' => 'structure', 'members' => [ 'accountScope' => [ 'shape' => 'String', ], 'term' => [ 'shape' => 'String', ], 'paymentOption' => [ 'shape' => 'String', ], 'hourlyCommitment' => [ 'shape' => 'String', ], ], ], 'Datetime' => [ 'type' => 'timestamp', ], 'DbInstanceConfiguration' => [ 'type' => 'structure', 'members' => [ 'dbInstanceClass' => [ 'shape' => 'String', ], ], ], 'Double' => [ 'type' => 'double', 'box' => true, ], 'EbsVolume' => [ 'type' => 'structure', 'members' => [ 'configuration' => [ 'shape' => 'EbsVolumeConfiguration', ], 'costCalculation' => [ 'shape' => 'ResourceCostCalculation', ], ], ], 'EbsVolumeConfiguration' => [ 'type' => 'structure', 'members' => [ 'storage' => [ 'shape' => 'StorageConfiguration', ], 'performance' => [ 'shape' => 'BlockStoragePerformanceConfiguration', ], 'attachmentState' => [ 'shape' => 'String', ], ], ], 'Ec2AutoScalingGroup' => [ 'type' => 'structure', 'members' => [ 'configuration' => [ 'shape' => 'Ec2AutoScalingGroupConfiguration', ], 'costCalculation' => [ 'shape' => 'ResourceCostCalculation', ], ], ], 'Ec2AutoScalingGroupConfiguration' => [ 'type' => 'structure', 'members' => [ 'instance' => [ 'shape' => 'InstanceConfiguration', ], 'mixedInstances' => [ 'shape' => 'MixedInstanceConfigurationList', ], 'type' => [ 'shape' => 'Ec2AutoScalingGroupType', ], 'allocationStrategy' => [ 'shape' => 'AllocationStrategy', ], ], ], 'Ec2AutoScalingGroupType' => [ 'type' => 'string', 'enum' => [ 'SingleInstanceType', 'MixedInstanceTypes', ], ], 'Ec2Instance' => [ 'type' => 'structure', 'members' => [ 'configuration' => [ 'shape' => 'Ec2InstanceConfiguration', ], 'costCalculation' => [ 'shape' => 'ResourceCostCalculation', ], ], ], 'Ec2InstanceConfiguration' => [ 'type' => 'structure', 'members' => [ 'instance' => [ 'shape' => 'InstanceConfiguration', ], ], ], 'Ec2InstanceSavingsPlans' => [ 'type' => 'structure', 'members' => [ 'configuration' => [ 'shape' => 'Ec2InstanceSavingsPlansConfiguration', ], 'costCalculation' => [ 'shape' => 'SavingsPlansCostCalculation', ], ], ], 'Ec2InstanceSavingsPlansConfiguration' => [ 'type' => 'structure', 'members' => [ 'accountScope' => [ 'shape' => 'String', ], 'term' => [ 'shape' => 'String', ], 'paymentOption' => [ 'shape' => 'String', ], 'hourlyCommitment' => [ 'shape' => 'String', ], 'instanceFamily' => [ 'shape' => 'String', ], 'savingsPlansRegion' => [ 'shape' => 'String', ], ], ], 'Ec2ReservedInstances' => [ 'type' => 'structure', 'members' => [ 'configuration' => [ 'shape' => 'Ec2ReservedInstancesConfiguration', ], 'costCalculation' => [ 'shape' => 'ReservedInstancesCostCalculation', ], ], ], 'Ec2ReservedInstancesConfiguration' => [ 'type' => 'structure', 'members' => [ 'accountScope' => [ 'shape' => 'String', ], 'service' => [ 'shape' => 'String', ], 'normalizedUnitsToPurchase' => [ 'shape' => 'String', ], 'term' => [ 'shape' => 'String', ], 'paymentOption' => [ 'shape' => 'String', ], 'numberOfInstancesToPurchase' => [ 'shape' => 'String', ], 'offeringClass' => [ 'shape' => 'String', ], 'instanceFamily' => [ 'shape' => 'String', ], 'instanceType' => [ 'shape' => 'String', ], 'reservedInstancesRegion' => [ 'shape' => 'String', ], 'currentGeneration' => [ 'shape' => 'String', ], 'platform' => [ 'shape' => 'String', ], 'tenancy' => [ 'shape' => 'String', ], 'sizeFlexEligible' => [ 'shape' => 'Boolean', ], 'upfrontCost' => [ 'shape' => 'String', ], 'monthlyRecurringCost' => [ 'shape' => 'String', ], ], ], 'EcsService' => [ 'type' => 'structure', 'members' => [ 'configuration' => [ 'shape' => 'EcsServiceConfiguration', ], 'costCalculation' => [ 'shape' => 'ResourceCostCalculation', ], ], ], 'EcsServiceConfiguration' => [ 'type' => 'structure', 'members' => [ 'compute' => [ 'shape' => 'ComputeConfiguration', ], ], ], 'ElastiCacheReservedInstances' => [ 'type' => 'structure', 'members' => [ 'configuration' => [ 'shape' => 'ElastiCacheReservedInstancesConfiguration', ], 'costCalculation' => [ 'shape' => 'ReservedInstancesCostCalculation', ], ], ], 'ElastiCacheReservedInstancesConfiguration' => [ 'type' => 'structure', 'members' => [ 'accountScope' => [ 'shape' => 'String', ], 'service' => [ 'shape' => 'String', ], 'normalizedUnitsToPurchase' => [ 'shape' => 'String', ], 'term' => [ 'shape' => 'String', ], 'paymentOption' => [ 'shape' => 'String', ], 'numberOfInstancesToPurchase' => [ 'shape' => 'String', ], 'instanceFamily' => [ 'shape' => 'String', ], 'instanceType' => [ 'shape' => 'String', ], 'reservedInstancesRegion' => [ 'shape' => 'String', ], 'currentGeneration' => [ 'shape' => 'String', ], 'sizeFlexEligible' => [ 'shape' => 'Boolean', ], 'upfrontCost' => [ 'shape' => 'String', ], 'monthlyRecurringCost' => [ 'shape' => 'String', ], ], ], 'EnrollmentStatus' => [ 'type' => 'string', 'enum' => [ 'Active', 'Inactive', ], ], 'EstimatedDiscounts' => [ 'type' => 'structure', 'members' => [ 'savingsPlansDiscount' => [ 'shape' => 'Double', ], 'reservedInstancesDiscount' => [ 'shape' => 'Double', ], 'otherDiscount' => [ 'shape' => 'Double', ], ], ], 'Filter' => [ 'type' => 'structure', 'members' => [ 'restartNeeded' => [ 'shape' => 'Boolean', ], 'rollbackPossible' => [ 'shape' => 'Boolean', ], 'implementationEfforts' => [ 'shape' => 'ImplementationEffortList', ], 'accountIds' => [ 'shape' => 'AccountIdList', ], 'regions' => [ 'shape' => 'RegionList', ], 'resourceTypes' => [ 'shape' => 'ResourceTypeList', ], 'actionTypes' => [ 'shape' => 'ActionTypeList', ], 'tags' => [ 'shape' => 'TagList', ], 'resourceIds' => [ 'shape' => 'ResourceIdList', ], 'resourceArns' => [ 'shape' => 'ResourceArnList', ], 'recommendationIds' => [ 'shape' => 'RecommendationIdList', ], ], ], 'GetPreferencesRequest' => [ 'type' => 'structure', 'members' => [], ], 'GetPreferencesResponse' => [ 'type' => 'structure', 'members' => [ 'savingsEstimationMode' => [ 'shape' => 'SavingsEstimationMode', ], 'memberAccountDiscountVisibility' => [ 'shape' => 'MemberAccountDiscountVisibility', ], ], ], 'GetRecommendationRequest' => [ 'type' => 'structure', 'required' => [ 'recommendationId', ], 'members' => [ 'recommendationId' => [ 'shape' => 'String', ], ], ], 'GetRecommendationResponse' => [ 'type' => 'structure', 'members' => [ 'recommendationId' => [ 'shape' => 'String', ], 'resourceId' => [ 'shape' => 'String', ], 'resourceArn' => [ 'shape' => 'String', ], 'accountId' => [ 'shape' => 'String', ], 'currencyCode' => [ 'shape' => 'String', ], 'recommendationLookbackPeriodInDays' => [ 'shape' => 'Integer', ], 'costCalculationLookbackPeriodInDays' => [ 'shape' => 'Integer', ], 'estimatedSavingsPercentage' => [ 'shape' => 'Double', ], 'estimatedSavingsOverCostCalculationLookbackPeriod' => [ 'shape' => 'Double', ], 'currentResourceType' => [ 'shape' => 'ResourceType', ], 'recommendedResourceType' => [ 'shape' => 'ResourceType', ], 'region' => [ 'shape' => 'String', ], 'source' => [ 'shape' => 'Source', ], 'lastRefreshTimestamp' => [ 'shape' => 'Datetime', ], 'estimatedMonthlySavings' => [ 'shape' => 'Double', ], 'estimatedMonthlyCost' => [ 'shape' => 'Double', ], 'implementationEffort' => [ 'shape' => 'ImplementationEffort', ], 'restartNeeded' => [ 'shape' => 'Boolean', ], 'actionType' => [ 'shape' => 'ActionType', ], 'rollbackPossible' => [ 'shape' => 'Boolean', ], 'currentResourceDetails' => [ 'shape' => 'ResourceDetails', ], 'recommendedResourceDetails' => [ 'shape' => 'ResourceDetails', ], 'tags' => [ 'shape' => 'TagList', ], ], ], 'ImplementationEffort' => [ 'type' => 'string', 'enum' => [ 'VeryLow', 'Low', 'Medium', 'High', 'VeryHigh', ], ], 'ImplementationEffortList' => [ 'type' => 'list', 'member' => [ 'shape' => 'ImplementationEffort', ], 'max' => 100, 'min' => 1, ], 'InstanceConfiguration' => [ 'type' => 'structure', 'members' => [ 'type' => [ 'shape' => 'String', ], ], ], 'Integer' => [ 'type' => 'integer', 'box' => true, ], 'InternalServerException' => [ 'type' => 'structure', 'required' => [ 'message', ], 'members' => [ 'message' => [ 'shape' => 'String', ], ], 'exception' => true, 'fault' => true, ], 'LambdaFunction' => [ 'type' => 'structure', 'members' => [ 'configuration' => [ 'shape' => 'LambdaFunctionConfiguration', ], 'costCalculation' => [ 'shape' => 'ResourceCostCalculation', ], ], ], 'LambdaFunctionConfiguration' => [ 'type' => 'structure', 'members' => [ 'compute' => [ 'shape' => 'ComputeConfiguration', ], ], ], 'ListEnrollmentStatusesRequest' => [ 'type' => 'structure', 'members' => [ 'includeOrganizationInfo' => [ 'shape' => 'PrimitiveBoolean', ], 'accountId' => [ 'shape' => 'AccountId', ], 'nextToken' => [ 'shape' => 'String', ], 'maxResults' => [ 'shape' => 'MaxResults', ], ], ], 'ListEnrollmentStatusesResponse' => [ 'type' => 'structure', 'members' => [ 'items' => [ 'shape' => 'AccountEnrollmentStatuses', ], 'includeMemberAccounts' => [ 'shape' => 'Boolean', ], 'nextToken' => [ 'shape' => 'String', ], ], ], 'ListRecommendationSummariesRequest' => [ 'type' => 'structure', 'required' => [ 'groupBy', ], 'members' => [ 'filter' => [ 'shape' => 'Filter', ], 'groupBy' => [ 'shape' => 'String', ], 'maxResults' => [ 'shape' => 'ListRecommendationSummariesRequestMaxResultsInteger', ], 'metrics' => [ 'shape' => 'SummaryMetricsList', ], 'nextToken' => [ 'shape' => 'String', ], ], ], 'ListRecommendationSummariesRequestMaxResultsInteger' => [ 'type' => 'integer', 'box' => true, 'max' => 1000, 'min' => 0, ], 'ListRecommendationSummariesResponse' => [ 'type' => 'structure', 'members' => [ 'estimatedTotalDedupedSavings' => [ 'shape' => 'Double', ], 'items' => [ 'shape' => 'RecommendationSummariesList', ], 'groupBy' => [ 'shape' => 'String', ], 'currencyCode' => [ 'shape' => 'String', ], 'metrics' => [ 'shape' => 'SummaryMetricsResult', ], 'nextToken' => [ 'shape' => 'String', ], ], ], 'ListRecommendationsRequest' => [ 'type' => 'structure', 'members' => [ 'filter' => [ 'shape' => 'Filter', ], 'orderBy' => [ 'shape' => 'OrderBy', ], 'includeAllRecommendations' => [ 'shape' => 'PrimitiveBoolean', ], 'maxResults' => [ 'shape' => 'ListRecommendationsRequestMaxResultsInteger', ], 'nextToken' => [ 'shape' => 'String', ], ], ], 'ListRecommendationsRequestMaxResultsInteger' => [ 'type' => 'integer', 'box' => true, 'max' => 1000, 'min' => 0, ], 'ListRecommendationsResponse' => [ 'type' => 'structure', 'members' => [ 'items' => [ 'shape' => 'RecommendationList', ], 'nextToken' => [ 'shape' => 'String', ], ], ], 'MaxResults' => [ 'type' => 'integer', 'box' => true, 'max' => 100, 'min' => 0, ], 'MemberAccountDiscountVisibility' => [ 'type' => 'string', 'enum' => [ 'All', 'None', ], ], 'MixedInstanceConfiguration' => [ 'type' => 'structure', 'members' => [ 'type' => [ 'shape' => 'String', ], ], ], 'MixedInstanceConfigurationList' => [ 'type' => 'list', 'member' => [ 'shape' => 'MixedInstanceConfiguration', ], ], 'OpenSearchReservedInstances' => [ 'type' => 'structure', 'members' => [ 'configuration' => [ 'shape' => 'OpenSearchReservedInstancesConfiguration', ], 'costCalculation' => [ 'shape' => 'ReservedInstancesCostCalculation', ], ], ], 'OpenSearchReservedInstancesConfiguration' => [ 'type' => 'structure', 'members' => [ 'accountScope' => [ 'shape' => 'String', ], 'service' => [ 'shape' => 'String', ], 'normalizedUnitsToPurchase' => [ 'shape' => 'String', ], 'term' => [ 'shape' => 'String', ], 'paymentOption' => [ 'shape' => 'String', ], 'numberOfInstancesToPurchase' => [ 'shape' => 'String', ], 'instanceType' => [ 'shape' => 'String', ], 'reservedInstancesRegion' => [ 'shape' => 'String', ], 'currentGeneration' => [ 'shape' => 'String', ], 'sizeFlexEligible' => [ 'shape' => 'Boolean', ], 'upfrontCost' => [ 'shape' => 'String', ], 'monthlyRecurringCost' => [ 'shape' => 'String', ], ], ], 'Order' => [ 'type' => 'string', 'enum' => [ 'Asc', 'Desc', ], ], 'OrderBy' => [ 'type' => 'structure', 'members' => [ 'dimension' => [ 'shape' => 'String', ], 'order' => [ 'shape' => 'Order', ], ], ], 'PrimitiveBoolean' => [ 'type' => 'boolean', ], 'RdsDbInstance' => [ 'type' => 'structure', 'members' => [ 'configuration' => [ 'shape' => 'RdsDbInstanceConfiguration', ], 'costCalculation' => [ 'shape' => 'ResourceCostCalculation', ], ], ], 'RdsDbInstanceConfiguration' => [ 'type' => 'structure', 'members' => [ 'instance' => [ 'shape' => 'DbInstanceConfiguration', ], ], ], 'RdsDbInstanceStorage' => [ 'type' => 'structure', 'members' => [ 'configuration' => [ 'shape' => 'RdsDbInstanceStorageConfiguration', ], 'costCalculation' => [ 'shape' => 'ResourceCostCalculation', ], ], ], 'RdsDbInstanceStorageConfiguration' => [ 'type' => 'structure', 'members' => [ 'storageType' => [ 'shape' => 'String', ], 'allocatedStorageInGb' => [ 'shape' => 'Double', ], 'iops' => [ 'shape' => 'Double', ], 'storageThroughput' => [ 'shape' => 'Double', ], ], ], 'RdsReservedInstances' => [ 'type' => 'structure', 'members' => [ 'configuration' => [ 'shape' => 'RdsReservedInstancesConfiguration', ], 'costCalculation' => [ 'shape' => 'ReservedInstancesCostCalculation', ], ], ], 'RdsReservedInstancesConfiguration' => [ 'type' => 'structure', 'members' => [ 'accountScope' => [ 'shape' => 'String', ], 'service' => [ 'shape' => 'String', ], 'normalizedUnitsToPurchase' => [ 'shape' => 'String', ], 'term' => [ 'shape' => 'String', ], 'paymentOption' => [ 'shape' => 'String', ], 'numberOfInstancesToPurchase' => [ 'shape' => 'String', ], 'instanceFamily' => [ 'shape' => 'String', ], 'instanceType' => [ 'shape' => 'String', ], 'reservedInstancesRegion' => [ 'shape' => 'String', ], 'sizeFlexEligible' => [ 'shape' => 'Boolean', ], 'currentGeneration' => [ 'shape' => 'String', ], 'upfrontCost' => [ 'shape' => 'String', ], 'monthlyRecurringCost' => [ 'shape' => 'String', ], 'licenseModel' => [ 'shape' => 'String', ], 'databaseEdition' => [ 'shape' => 'String', ], 'databaseEngine' => [ 'shape' => 'String', ], 'deploymentOption' => [ 'shape' => 'String', ], ], ], 'Recommendation' => [ 'type' => 'structure', 'members' => [ 'recommendationId' => [ 'shape' => 'String', ], 'accountId' => [ 'shape' => 'String', ], 'region' => [ 'shape' => 'String', ], 'resourceId' => [ 'shape' => 'String', ], 'resourceArn' => [ 'shape' => 'String', ], 'currentResourceType' => [ 'shape' => 'String', ], 'recommendedResourceType' => [ 'shape' => 'String', ], 'estimatedMonthlySavings' => [ 'shape' => 'Double', ], 'estimatedSavingsPercentage' => [ 'shape' => 'Double', ], 'estimatedMonthlyCost' => [ 'shape' => 'Double', ], 'currencyCode' => [ 'shape' => 'String', ], 'implementationEffort' => [ 'shape' => 'String', ], 'restartNeeded' => [ 'shape' => 'Boolean', ], 'actionType' => [ 'shape' => 'String', ], 'rollbackPossible' => [ 'shape' => 'Boolean', ], 'currentResourceSummary' => [ 'shape' => 'String', ], 'recommendedResourceSummary' => [ 'shape' => 'String', ], 'lastRefreshTimestamp' => [ 'shape' => 'Datetime', ], 'recommendationLookbackPeriodInDays' => [ 'shape' => 'Integer', ], 'source' => [ 'shape' => 'Source', ], 'tags' => [ 'shape' => 'TagList', ], ], ], 'RecommendationIdList' => [ 'type' => 'list', 'member' => [ 'shape' => 'String', ], 'max' => 100, 'min' => 1, ], 'RecommendationList' => [ 'type' => 'list', 'member' => [ 'shape' => 'Recommendation', ], ], 'RecommendationSummariesList' => [ 'type' => 'list', 'member' => [ 'shape' => 'RecommendationSummary', ], ], 'RecommendationSummary' => [ 'type' => 'structure', 'members' => [ 'group' => [ 'shape' => 'String', ], 'estimatedMonthlySavings' => [ 'shape' => 'Double', ], 'recommendationCount' => [ 'shape' => 'Integer', ], ], ], 'RedshiftReservedInstances' => [ 'type' => 'structure', 'members' => [ 'configuration' => [ 'shape' => 'RedshiftReservedInstancesConfiguration', ], 'costCalculation' => [ 'shape' => 'ReservedInstancesCostCalculation', ], ], ], 'RedshiftReservedInstancesConfiguration' => [ 'type' => 'structure', 'members' => [ 'accountScope' => [ 'shape' => 'String', ], 'service' => [ 'shape' => 'String', ], 'normalizedUnitsToPurchase' => [ 'shape' => 'String', ], 'term' => [ 'shape' => 'String', ], 'paymentOption' => [ 'shape' => 'String', ], 'numberOfInstancesToPurchase' => [ 'shape' => 'String', ], 'instanceFamily' => [ 'shape' => 'String', ], 'instanceType' => [ 'shape' => 'String', ], 'reservedInstancesRegion' => [ 'shape' => 'String', ], 'sizeFlexEligible' => [ 'shape' => 'Boolean', ], 'currentGeneration' => [ 'shape' => 'String', ], 'upfrontCost' => [ 'shape' => 'String', ], 'monthlyRecurringCost' => [ 'shape' => 'String', ], ], ], 'RegionList' => [ 'type' => 'list', 'member' => [ 'shape' => 'String', ], 'max' => 100, 'min' => 1, ], 'ReservedInstancesCostCalculation' => [ 'type' => 'structure', 'members' => [ 'pricing' => [ 'shape' => 'ReservedInstancesPricing', ], ], ], 'ReservedInstancesPricing' => [ 'type' => 'structure', 'members' => [ 'estimatedOnDemandCost' => [ 'shape' => 'Double', ], 'monthlyReservationEligibleCost' => [ 'shape' => 'Double', ], 'savingsPercentage' => [ 'shape' => 'Double', ], 'estimatedMonthlyAmortizedReservationCost' => [ 'shape' => 'Double', ], ], ], 'ResourceArnList' => [ 'type' => 'list', 'member' => [ 'shape' => 'String', ], 'max' => 100, 'min' => 1, ], 'ResourceCostCalculation' => [ 'type' => 'structure', 'members' => [ 'usages' => [ 'shape' => 'UsageList', ], 'pricing' => [ 'shape' => 'ResourcePricing', ], ], ], 'ResourceDetails' => [ 'type' => 'structure', 'members' => [ 'lambdaFunction' => [ 'shape' => 'LambdaFunction', ], 'ecsService' => [ 'shape' => 'EcsService', ], 'ec2Instance' => [ 'shape' => 'Ec2Instance', ], 'ebsVolume' => [ 'shape' => 'EbsVolume', ], 'ec2AutoScalingGroup' => [ 'shape' => 'Ec2AutoScalingGroup', ], 'ec2ReservedInstances' => [ 'shape' => 'Ec2ReservedInstances', ], 'rdsReservedInstances' => [ 'shape' => 'RdsReservedInstances', ], 'elastiCacheReservedInstances' => [ 'shape' => 'ElastiCacheReservedInstances', ], 'openSearchReservedInstances' => [ 'shape' => 'OpenSearchReservedInstances', ], 'redshiftReservedInstances' => [ 'shape' => 'RedshiftReservedInstances', ], 'ec2InstanceSavingsPlans' => [ 'shape' => 'Ec2InstanceSavingsPlans', ], 'computeSavingsPlans' => [ 'shape' => 'ComputeSavingsPlans', ], 'sageMakerSavingsPlans' => [ 'shape' => 'SageMakerSavingsPlans', ], 'rdsDbInstance' => [ 'shape' => 'RdsDbInstance', ], 'rdsDbInstanceStorage' => [ 'shape' => 'RdsDbInstanceStorage', ], ], 'union' => true, ], 'ResourceIdList' => [ 'type' => 'list', 'member' => [ 'shape' => 'String', ], 'max' => 100, 'min' => 1, ], 'ResourceNotFoundException' => [ 'type' => 'structure', 'required' => [ 'message', 'resourceId', ], 'members' => [ 'message' => [ 'shape' => 'String', ], 'resourceId' => [ 'shape' => 'String', ], ], 'exception' => true, ], 'ResourcePricing' => [ 'type' => 'structure', 'members' => [ 'estimatedCostBeforeDiscounts' => [ 'shape' => 'Double', ], 'estimatedNetUnusedAmortizedCommitments' => [ 'shape' => 'Double', ], 'estimatedDiscounts' => [ 'shape' => 'EstimatedDiscounts', ], 'estimatedCostAfterDiscounts' => [ 'shape' => 'Double', ], ], ], 'ResourceType' => [ 'type' => 'string', 'enum' => [ 'Ec2Instance', 'LambdaFunction', 'EbsVolume', 'EcsService', 'Ec2AutoScalingGroup', 'Ec2InstanceSavingsPlans', 'ComputeSavingsPlans', 'SageMakerSavingsPlans', 'Ec2ReservedInstances', 'RdsReservedInstances', 'OpenSearchReservedInstances', 'RedshiftReservedInstances', 'ElastiCacheReservedInstances', 'RdsDbInstanceStorage', 'RdsDbInstance', ], ], 'ResourceTypeList' => [ 'type' => 'list', 'member' => [ 'shape' => 'ResourceType', ], 'max' => 100, 'min' => 1, ], 'SageMakerSavingsPlans' => [ 'type' => 'structure', 'members' => [ 'configuration' => [ 'shape' => 'SageMakerSavingsPlansConfiguration', ], 'costCalculation' => [ 'shape' => 'SavingsPlansCostCalculation', ], ], ], 'SageMakerSavingsPlansConfiguration' => [ 'type' => 'structure', 'members' => [ 'accountScope' => [ 'shape' => 'String', ], 'term' => [ 'shape' => 'String', ], 'paymentOption' => [ 'shape' => 'String', ], 'hourlyCommitment' => [ 'shape' => 'String', ], ], ], 'SavingsEstimationMode' => [ 'type' => 'string', 'enum' => [ 'BeforeDiscounts', 'AfterDiscounts', ], ], 'SavingsPlansCostCalculation' => [ 'type' => 'structure', 'members' => [ 'pricing' => [ 'shape' => 'SavingsPlansPricing', ], ], ], 'SavingsPlansPricing' => [ 'type' => 'structure', 'members' => [ 'monthlySavingsPlansEligibleCost' => [ 'shape' => 'Double', ], 'estimatedMonthlyCommitment' => [ 'shape' => 'Double', ], 'savingsPercentage' => [ 'shape' => 'Double', ], 'estimatedOnDemandCost' => [ 'shape' => 'Double', ], ], ], 'Source' => [ 'type' => 'string', 'enum' => [ 'ComputeOptimizer', 'CostExplorer', ], ], 'StorageConfiguration' => [ 'type' => 'structure', 'members' => [ 'type' => [ 'shape' => 'String', ], 'sizeInGb' => [ 'shape' => 'Double', ], ], ], 'String' => [ 'type' => 'string', ], 'SummaryMetrics' => [ 'type' => 'string', 'enum' => [ 'SavingsPercentage', ], ], 'SummaryMetricsList' => [ 'type' => 'list', 'member' => [ 'shape' => 'SummaryMetrics', ], 'max' => 100, 'min' => 1, ], 'SummaryMetricsResult' => [ 'type' => 'structure', 'members' => [ 'savingsPercentage' => [ 'shape' => 'String', ], ], ], 'Tag' => [ 'type' => 'structure', 'members' => [ 'key' => [ 'shape' => 'String', ], 'value' => [ 'shape' => 'String', ], ], ], 'TagList' => [ 'type' => 'list', 'member' => [ 'shape' => 'Tag', ], 'max' => 100, 'min' => 1, ], 'ThrottlingException' => [ 'type' => 'structure', 'members' => [ 'message' => [ 'shape' => 'String', ], ], 'exception' => true, ], 'Timestamp' => [ 'type' => 'timestamp', ], 'UpdateEnrollmentStatusRequest' => [ 'type' => 'structure', 'required' => [ 'status', ], 'members' => [ 'status' => [ 'shape' => 'EnrollmentStatus', ], 'includeMemberAccounts' => [ 'shape' => 'Boolean', ], ], ], 'UpdateEnrollmentStatusResponse' => [ 'type' => 'structure', 'members' => [ 'status' => [ 'shape' => 'String', ], ], ], 'UpdatePreferencesRequest' => [ 'type' => 'structure', 'members' => [ 'savingsEstimationMode' => [ 'shape' => 'SavingsEstimationMode', ], 'memberAccountDiscountVisibility' => [ 'shape' => 'MemberAccountDiscountVisibility', ], ], ], 'UpdatePreferencesResponse' => [ 'type' => 'structure', 'members' => [ 'savingsEstimationMode' => [ 'shape' => 'SavingsEstimationMode', ], 'memberAccountDiscountVisibility' => [ 'shape' => 'MemberAccountDiscountVisibility', ], ], ], 'Usage' => [ 'type' => 'structure', 'members' => [ 'usageType' => [ 'shape' => 'String', ], 'usageAmount' => [ 'shape' => 'Double', ], 'operation' => [ 'shape' => 'String', ], 'productCode' => [ 'shape' => 'String', ], 'unit' => [ 'shape' => 'String', ], ], ], 'UsageList' => [ 'type' => 'list', 'member' => [ 'shape' => 'Usage', ], ], 'ValidationException' => [ 'type' => 'structure', 'required' => [ 'message', ], 'members' => [ 'message' => [ 'shape' => 'String', ], 'reason' => [ 'shape' => 'ValidationExceptionReason', ], 'fields' => [ 'shape' => 'ValidationExceptionDetails', ], ], 'exception' => true, ], 'ValidationExceptionDetail' => [ 'type' => 'structure', 'required' => [ 'fieldName', 'message', ], 'members' => [ 'fieldName' => [ 'shape' => 'String', ], 'message' => [ 'shape' => 'String', ], ], ], 'ValidationExceptionDetails' => [ 'type' => 'list', 'member' => [ 'shape' => 'ValidationExceptionDetail', ], ], 'ValidationExceptionReason' => [ 'type' => 'string', 'enum' => [ 'FieldValidationFailed', 'Other', ], ], ],];
